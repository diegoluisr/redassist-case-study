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
 * Class that process the data input.
 */
class TelegramProcessInputData {

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
  public function getInputDataFromMesssage($message) {
    if (!str_starts_with($message, '/')) {
      return $message;
    }

    return NULL;
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function processInputData($update) {
    $chatId = $this->tisTelegram->getChatId($update);
    $inputData = $this->getInputDataFromMesssage($update['message']);
    $userFlowSteps = $this->telegramBridgeWithSales->getUserFlowSteps($update);

    if (is_null($inputData)) {
      return NULL;
    }

    // Data sent by users.
    $this->telegramBridgeWithSales->storeInputData($userFlowSteps,
      $update,
      $this->telegramSaveSale->getCurrentFlowStep($userFlowSteps)
    );

    // Response to know data.
    switch ($inputData) {

      case TelegramBridgeWithSales::NEW_SALE_MENU_ITEM:
        // Generate sale token.
        // $token = $this->telegramBridgeWithSales->startNewSale($update);.
        // $this->telegramSaveSale->showToken($update, $token, $chatId);.
        $this->telegramSaveSale->getNextFlowStep($userFlowSteps, $update, $chatId);
        break;

      case TelegramBridgeWithSales::SUPPORT_MENU_ITEM:
        $this->telegramSaveSale->showSupportLink($chatId);
        break;

      case TelegramBridgeWithSales::VISIT_WEBSITE_MENU_ITEM:
        $this->telegramSaveSale->showWebLink($chatId);
        break;

      case '✅ Si, estoy listo':
        $this->telegramSaveSale->startSalesFlow($update);
        $this->telegramSaveSale->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        break;

      case 'No, por favor cancelar esta solicitud':
        $this->tisTelegram->send('Hemos cancelado tu solicitud...', $chatId);
        $this->telegramSaveSale->showWelcomeMessage($update, $chatId);
        break;

      case '✅ Si, es mi nombre':
        $this->tisTelegram->send('Perfecto, voy a guardarlo...', $chatId);
        // Skip two steps, the full name is already store.
        $this->telegramSaveSale->goToFlowStep($userFlowSteps, $update, $chatId, 4);
        break;

      case 'No, debemos actualizarlo':
        $this->telegramBridgeWithSales->cleanUserFullName($update);
        $this->telegramSaveSale->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        break;

      case '✅ Si, esta todo en orden':
        $this->telegramSaveSale->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE);
        break;

      case 'No, empezar nuevamente':
        $this->telegramSaveSale->cancelSaleRequest($update, $chatId);
        break;

      default:
        $this->telegramSaveSale->completeCurrentStep($userFlowSteps, $update, $chatId, TRUE, TRUE);
        break;

    }

  }

}
