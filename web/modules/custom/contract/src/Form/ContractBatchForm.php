<?php

namespace Drupal\contract\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\contract\Service\ContractService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form contract batch.
 */
class ContractBatchForm extends ConfigFormBase {

  /**
   * The service for processing batch.
   *
   * @var \Drupal\contract\Service\ContractService
   */
  protected $contractService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new form object.
   *
   * @param \Drupal\contract\Service\ContractService $contractService
   *   The service for processing batch package.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger service.
   */
  public function __construct(
    ContractService $contractService,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger
  ) {
    $this->contractService = $contractService;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('contract.service'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contract_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['contract_batch_form.sync'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => $this->t('<div>Iniciar actualización de la infomación de los contratos.</div>'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Iniciar'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batchBuilder = (new BatchBuilder())
      ->setTitle($this->t('Reemplazando datos de los contratos en la Base de datos'))
      ->setFinishCallback([$this->contractService, 'finish']);

    $entities = $this->entityTypeManager->getStorage('contract')->loadMultiple();

    /** @var \Drupal\contract\Entity\Contract $entity */
    foreach ($entities as $entity) {
      $batchBuilder->addOperation(
        [
          $this->contractService,
          'processContractEntity',
        ],
        [
          $entity,
        ]
      );
    }

    batch_set($batchBuilder->toArray());
  }

}
