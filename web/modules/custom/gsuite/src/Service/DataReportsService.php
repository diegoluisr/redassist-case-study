<?php

namespace Drupal\gsuite\Service;

use Drupal\b2c\Service\BusinessService;
use Drupal\b2c\Service\DataService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\digitalsign\Service\DigitalSign;
use Drupal\digitalsign\Service\HelperDigitalSign;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Service to obtain data.
 */
class DataReportsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The digital sign service.
   *
   * @var \Drupal\digitalsign\Service\DigitalSign\Drupal\Core\Extension\ModuleHandler
   */
  protected $digitalsign;

  /**
   * The B2C Data.
   *
   * @var \Drupal\b2c\Service\DataService
   */
  protected $b2cData;

  /**
   * The digital sign helper.
   *
   * @var \Drupal\digitalsign\Service\HelperDigitalSign
   */
  protected $digitalSignHelper;

  /**
   * The Business Service.
   *
   * @var Drupal\b2c\Service\BusinessService
   */
  protected $businessService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    DigitalSign $digitalsign,
    DataService $b2cData,
    HelperDigitalSign $digitalSignHelper,
    BusinessService $businessService
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->digitalsign = $digitalsign;
    $this->b2cData = $b2cData;
    $this->digitalSignHelper = $digitalSignHelper;
    $this->businessService = $businessService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('digitalsign.service'),
      $container->get('b2c.data'),
      $container->get('digitalsign.helper'),
      $container->get('b2c.business'),
    );
  }

  /**
   * Function to get the order position for an order ID.
   */
  public function getPosition($orderId) {
    /** @var \Drupal\commerce_order\OrderStorage $orderStorage */
    $orderStorage = $this->entityTypeManager->getStorage('commerce_order');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $orderStorage->getQuery();

    $database = \Drupal::database();
    $query = $database->select('commerce_order', 'co', []);
    $query->fields('co', ['order_id']);
    $query->condition('order_id', intval($orderId), '<=');

    $total = $query->countQuery()->execute()->fetchField();

    return $total;
  }

  /**
   * The function that get the fields from an order.
   *
   * @param string $orderId
   *   The id from the order.
   */
  public function getDataFromOrder($orderId) {
    $contract_uuid = $this->digitalsign->getContractUuidByOrderId($orderId);
    /** @var \Drupal\contract\Entity\Contract $contract */
    $contract = $this->digitalsign->loadContractByUuid($contract_uuid);

    $manifest = $this->digitalSignHelper->verifyContractManifest($contract);

    if (!$manifest) {
      return;
    }
    $saleChannel = strval($this->businessService->getChannelByUser($manifest['order']['seller']['uid']));
    if (isset($manifest['order']['contract']['assistance']['channel'])) {
      $saleChannel = $manifest['order']['contract']['assistance']['channel'];
    }

    $dataInReport = [
      'id' => $orderId,
      'codigo' => strval($manifest['order']['contract']['serial']),
      'canal' => $saleChannel,
      'estado_de_venta' => $contract->get('status')->getValue()[0]['value'],
      'hora_de_realizacion' => strval(date('H:i:s', $manifest['order']['date']['datetime'])),
      'dia_de_contrato' => strval($manifest['order']['date']['day']),
      'año_de_contrato' => strval($manifest['order']['date']['year']),
      'mes_de_contrato' => strval($manifest['order']['date']['month']),
      'primer_nombre' => strval($manifest['signers'][0]['basic_info']['name']['first']),
      'primer_apellido' => strval($manifest['signers'][0]['basic_info']['lastname']['first']),
      'numero_de_identificacion' => strval($manifest['signers'][0]['basic_info']['docid']['num']),
      'tipo_de_identificacion' => strval($manifest['signers'][0]['basic_info']['docid']['name']),
      'tipo_de_vivienda' => strval($manifest['order']['contract']['assistance']['ownership']['name']),
      'estrato' => strval($manifest['order']['contract']['assistance']['stratus']),
      'nip1' => strval($manifest['order']['contract']['nips']['nip1']),
      'asistencia' => strval($manifest['order']['contract']['assistance']['name']),
      'departamento' => strval($manifest['signers'][0]['contact_info']['state']['name']),
      'ciudad' => strval($manifest['signers'][0]['contact_info']['city']['name']),
      'direccion_completa' => strval($manifest['signers'][0]['contact_info']['address']['full']),
      'telefono_fijo' => strval($manifest['signers'][0]['contact_info']['phone']),
      'telefono_movil' => strval($manifest['signers'][0]['contact_info']['mobile']),
      'vendedor' => strval($manifest['order']['seller']['email']),
      'nombre_vendedor' => strval($manifest['order']['seller']['name']),
    ];

    return $dataInReport;
  }

}
