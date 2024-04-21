<?php

namespace Drupal\tis\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tis\Form\TisSettingsForm;

/**
 * Class TelegramProcessInputData.
 */
class TelegramProcessSignature {

  use StringTranslationTrait;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityField;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Var that stores config Factory Services.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The unwanted fields array.
   *
   * @var array
   */
  protected $unwantedFields;

  /**
   * The Messenger service.
   *
   * @var array
   */
  protected $inlineEntities;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramService
   */
  protected $tisTelegram;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramSaveSale
   */
  protected $telegramSaveSale;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramBridgeWithSales
   */
  protected $telegramBridgeWithSales;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityField
   *   The file storage backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The file storage backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The bundle type info.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\tis\Service\TelegramService $tisTelegram
   *   The tis Telegram service.
   * @param \Drupal\tis\Service\TelegramSaveSale $telegramSaveSale
   *   The tis Telegram save sale service.
   * @param \Drupal\tis\Service\TelegramBridgeWithSales $telegramBridgeWithSales
   *   The tis Telegram service.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    EntityFieldManagerInterface $entityField,
    EntityTypeManagerInterface $entityTypeManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    FileSystemInterface $fileSystem = NULL,
    LoggerChannelFactoryInterface $logger,
    MessengerInterface $messenger,
    StateInterface $state,
    TelegramService $tisTelegram,
    TelegramSaveSale $telegramSaveSale,
    TelegramBridgeWithSales $telegramBridgeWithSales
  ) {
    $this->config = $config;
    $this->settings = $config->getEditable(TisSettingsForm::SETTINGS);
    $this->entityField = $entityField;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->fileSystem = $fileSystem;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->state = $state;
    $this->tisTelegram = $tisTelegram;
    $this->telegramSaveSale = $telegramSaveSale;
    $this->telegramBridgeWithSales = $telegramBridgeWithSales;
  }

  /**
   * Function to get input data.
   */
  public function getSignatureToken($update) {
    if (isset($update['signature']['ts']) && !empty($update['signature']['ts'])) {
      return $update['signature']['ts'];
    }
    return '';
  }

  /**
   * Function to get input data.
   */
  public function getSignatureImage($update) {
    if (isset($update['signature']['image']) && !empty($update['signature']['image'])) {
      return $update['signature']['image'];
    }
    return '';
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function processSignature($update) {
    $chatId = $this->tisTelegram->getChatId($update);
    $userFlowSteps = $this->telegramBridgeWithSales->getUserFlowSteps($update);
    $token = $this->telegramBridgeWithSales->getUserToken($update);

    if ($token === $this->getSignatureToken($update)) {
      $update['message'] = $this->getSignatureImage($update);

      if ($this->telegramSaveSale->shouldProcessSignature($userFlowSteps)) {
        $this->telegramSaveSale->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
      }
      else {
        $this->tisTelegram->send('La firma no puede ser almacenada en este momento.', $chatId, NULL, FALSE);
      }
    }

  }

}
