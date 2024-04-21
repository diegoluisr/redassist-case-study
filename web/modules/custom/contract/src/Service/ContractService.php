<?php

namespace Drupal\contract\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\contract\Entity\Contract;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\digitalsign\Controller\SaleWorkflowController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Contract Service.
 */
class ContractService implements ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * B2cHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The file storage backend.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger factory.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    Messenger $messenger,
    LoggerChannelFactory $logger,
    DateFormatter $dateFormatter
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * Function to create an empty contract.
   */
  public function createEmpty($name, $bundle, $status = NULL) {
    if ($status === NULL) {
      $status = SaleWorkflowController::STATUS_NEW;
    }

    $contract = $this->entityTypeManager->getStorage('contract')->create([
      'name' => $name,
      'bundle' => $bundle,
      'status' => $status,
    ]);

    return $contract;
  }

  /**
   * Processes the entity contract data.
   *
   * @param \Drupal\contract\Entity\Contract $contract
   *   The order entity.
   * @param array|\ArrayAccess $context
   *   The batch context.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function processContractEntity(Contract $contract, &$context) {
    if (empty($context['results'])) {
      $context['results']['sucess'] = 0;
      $context['results']['fail'] = 0;
      $context['results']['count'] = 0;
      $context['results']['error'] = [];
    }

    try {
      $contract->set('bundle', 'sale');
      $contract->save();

      $context['results']['sucess']++;
    }
    catch (EntityStorageException $error) {
      $context['results']['error'][] = $error;
      $context['results']['fail']++;
    }

    $context['results']['count']++;
  }

  /**
   * Finish batch.
   *
   * @param bool $success
   *   Indicates whether the batch process was successful.
   * @param array $results
   *   Results information passed from the processing callback.
   */
  public function finish($success, array $results) {
    if ($success) {
      if (isset($results['count'])) {
        $this->messenger->addMessage($this->t('Contracts - @count were processed.', [
          '@count' => $results['count'],
        ]));
      }

      if (isset($results['sucess'])) {
        $this->messenger->addMessage($this->t('Contracts - @count were updated.', [
          '@count' => $results['sucess'],
        ]));
      }

      if (isset($results['fail'])) {
        $this->messenger->addMessage($this->t('Contracts - @count fail.', [
          '@count' => $results['fail'],
        ]));
      }

      if (isset($results['error'])) {
        if (count($results['error']) > 0) {
          $this->messenger->addError($this->t('Contracts were not updated to the following states: @error.', [
            '@error' => implode(", ", $results['error']),
          ]));
        }
      }
    }
    else {
      $this->messenger->addError($this->t('An error occurred trying to replaced Info Entity.'));
    }
  }

}
