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
use Drupal\Core\Url;
use Drupal\tis\Form\TisSettingsForm;
use TgBotApi\BotApiBase\Method\AnswerCallbackQueryMethod;
use TgBotApi\BotApiBase\Type\InlineKeyboardButtonType;
use TgBotApi\BotApiBase\Type\InlineKeyboardMarkupType;

/**
 * Class ExportService.
 */
class TelegramAnswersKeyboard {

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
   * Function to get the callback data froma a text message.
   */
  public function getCallbackDataFromMesssage($callbackQuery) {
    if (is_array($callbackQuery) && isset($callbackQuery['data'])) {
      return $callbackQuery['data'];
    }
    return NULL;
  }

  /**
   * Function to get the callback game data froma a text message.
   */
  public function getCallbackGameNameFromMesssage($callbackQuery) {
    if (is_array($callbackQuery) && isset($callbackQuery['game_short_name'])) {
      return $callbackQuery['game_short_name'];
    }
    return NULL;
  }

  /**
   * Function to get Telegram response for commands.
   */
  public function getAnswerResponse($update) {
    $chatId = $this->tisTelegram->getChatId($update);
    $callbackQuery = $update['callback_query'];
    $callbackData = $this->getCallbackDataFromMesssage($callbackQuery);
    $callbackGame = $this->getCallbackGameNameFromMesssage($callbackQuery);

    // If is signature web app/game.
    if (!is_null($callbackGame)) {
      switch ($callbackGame) {
        case 'B2CSignature':
          $update['data'] = $callbackQuery;
          $update['data']['message']['from'] = $callbackQuery['message']['chat'];
          $token = $this->telegramBridgeWithSales->getUserToken($update);
          $chatId = $this->tisTelegram->getChatId($update);

          $gameUrl = Url::fromRoute(
            'til.signature',
            [],
            [
              'absolute' => TRUE,
              'https' => TRUE,
            ]
          )->toString() . '/#ts=' . $token . '&chat=' . $chatId;

          $answer = AnswerCallbackQueryMethod::create($callbackQuery['id'], [
            'text' => 'Estamos cargando la aplicación de firma digital...',
            'showAlert' => TRUE,
            'url' => $gameUrl,
          ]);
          $this->tisTelegram->sendAnswerCallbackQuery($answer);
          break;
      }
    }

    // If is keyboard.
    if (!is_null($callbackData)) {
      $this->logger->get('tis_answerkeyboard_data')->info(print_r($callbackData, TRUE));
      switch ($callbackData) {

        case 'terms_read_link':
          $replyMarkup = InlineKeyboardMarkupType::create([
            [
              InlineKeyboardButtonType::create(
                'Ir al sitio web de B2C',
                ['url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString()]
              ),
            ],
          ]);
          $this->tisTelegram->send('En el siguiente link podras encontrar nuestros terminos y condiciones de uso', $chatId, $replyMarkup);
          break;

        case 'yes':
        case 'no':
        case 'stop':
          $answer = AnswerCallbackQueryMethod::create($callbackQuery['id'], [
            'text' => 'esta decidido',
            'showAlert' => TRUE,
          ]);
          $this->tisTelegram->sendAnswerCallbackQuery($answer);
          break;

        case 'newsale':
          $answer = AnswerCallbackQueryMethod::create($callbackQuery['id'], [
            'text' => 'quiere guardar una nueva venta',
          ]);
          $this->tisTelegram->sendAnswerCallbackQuery($answer);
          break;

        case 'otras':
          $answer = AnswerCallbackQueryMethod::create($callbackQuery['id'], [
            'text' => 'quiere ver otras cosas',
          ]);
          $this->tisTelegram->sendAnswerCallbackQuery($answer);
          break;
      }
    }

  }

}
