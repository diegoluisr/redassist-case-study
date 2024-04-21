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
 * Class ExportService.
 */
class TelegramCommands {

  use StringTranslationTrait;

  /**
   * The entity type .
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
   * @var \Drupal\tis\Service\TelegramBridgeWithSales
   */
  protected $telegramBridgeWithSales;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramSaveSale
   */
  protected $telegramSaveSale;

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
   *   The tis bridge with sales  service.
   * @param \Drupal\tis\Service\TelegramBridgeWithSales $telegramBridgeWithSales
   *   The tis Telegram service.
   * @param \Drupal\tis\Service\TelegramSaveSale $telegramSaveSale
   *   The tis Telegram save sale service.
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
    TelegramBridgeWithSales $telegramBridgeWithSales,
    TelegramSaveSale $telegramSaveSale
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
    $this->telegramBridgeWithSales = $telegramBridgeWithSales;
    $this->telegramSaveSale = $telegramSaveSale;
  }

  /**
   * Function to get a command froma a text message.
   */
  public function getCommandFromMesssage($message) {
    if (str_starts_with($message, '/')) {
      $message = str_word_count($message, 1);
      if (is_array($message)) {
        $command = [];
        $command['command'] = '/' . $message[0];
        if (isset($message[1]) && !empty($message[1])) {
          $command['params'] = $message[1];
        }
        return $command;
      }
    }

    return NULL;
  }

  /**
   * Function to register commands in bot.
   */
  public function commandsToRegister() {
    return [
      [
        'command' => 'start',
        'description' => 'Solicitar un servicio',
      ],
      [
        'command' => 'terminos',
        'description' => 'Ver condiciones del servicio',
      ],
      [
        'command' => 'soporte',
        'description' => 'Hablar con un asesor',
      ],
    ];
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function getCommandResponse($update) {

    $chatId = $this->tisTelegram->getChatId($update);
    $command = $this->getCommandFromMesssage($update['message']);

    if (is_null($command)) {
      return NULL;
    }

    switch ($command['command']) {
      case '/start':
        // Update commands register.
        $this->tisTelegram->registerCommands($this->commandsToRegister());
        $this->telegramSaveSale->showWelcomeMessage($update, $chatId);
        break;

      case '/terminos':
        $this->telegramSaveSale->showTermsLink($chatId);
        break;

      case '/soporte':
        $this->telegramSaveSale->showSupportLink($chatId);
        break;

      case '/seeusersalesflow':
        $interactionData = $this->telegramBridgeWithSales->getUserInteractionData($update);
        $this->logger->get('tis_inputlog')->info(print_r($interactionData, TRUE));
        break;

      case '/seemanifest':
        $token = $this->telegramBridgeWithSales->getUserToken($update);
        if (!empty($token)) {
          $manifest = $this->telegramBridgeWithSales->getFieldsToFill($token);
          $this->logger->get('tis_manifest')->info(print_r($manifest, TRUE));
        }
        break;

      case '/seeinputlog':
        $interactionData = $this->telegramBridgeWithSales->getUserInteractionData($update);
        $this->logger->get('tis_inputlog')->info(print_r($interactionData['inputlog'], TRUE));
        break;

      case '/seeusername':
        $interactionData = $this->telegramBridgeWithSales->getUserInteractionData($update);
        $this->logger->get('tis_seeusername')->info(print_r($interactionData['primer_nombre'], TRUE));
        $this->logger->get('tis_seeusername')->info(print_r($interactionData['segundo_nombre'], TRUE));
        $this->logger->get('tis_seeusername')->info(print_r($interactionData['primer_apellido'], TRUE));
        $this->logger->get('tis_seeusername')->info(print_r($interactionData['segundo_apellido'], TRUE));
        $this->logger->get('tis_seeusername')->info(print_r($interactionData['fullname'], TRUE));
        break;

      default:
        $this->tisTelegram->send('No reconozco este comando. Verifica si lo escribiste bien.', $chatId);
        break;
    }

  }

}
