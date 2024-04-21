<?php

namespace Drupal\tis\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tis\Form\TisSettingsForm;
use GuzzleHttp\Exception\ClientException;
use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use TgBotApi\BotApiBase\ApiClient;
use TgBotApi\BotApiBase\BotApi;
use TgBotApi\BotApiBase\BotApiNormalizer;
use TgBotApi\BotApiBase\Exception\ResponseException;
use TgBotApi\BotApiBase\Method\DeleteWebhookMethod;
use TgBotApi\BotApiBase\Method\GetChatMemberMethod;
use TgBotApi\BotApiBase\Method\GetFileMethod;
use TgBotApi\BotApiBase\Method\GetMyCommandsMethod;
use TgBotApi\BotApiBase\Method\GetUpdatesMethod;
use TgBotApi\BotApiBase\Method\Interfaces\AnswerMethodAliasInterface;
use TgBotApi\BotApiBase\Method\Interfaces\HasParseModeVariableInterface;
use TgBotApi\BotApiBase\Method\SendMessageMethod;
use TgBotApi\BotApiBase\Method\SetMyCommandsMethod;
use TgBotApi\BotApiBase\Method\SetWebhookMethod;
use TgBotApi\BotApiBase\Type\BotCommandType;
use TgBotApi\BotApiBase\Type\FileType;
use TgBotApi\BotApiBase\Type\ReplyKeyboardRemoveType;

/**
 * Class ExportService.
 */
class TelegramService {

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
   * Variable that store the HTTP client.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $client;

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
   * @param \Drupal\Core\Http\ClientFactory $client
   *   Var that stores the HTTP client.
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
    ClientFactory $client
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
    $this->client = $client;
  }

  /**
   * Function to build importante request vars.
   */
  public function buildRequestVars($request) {

    $request = Json::decode($request);
    $update = [];

    if (isset($request['message']['text']) && !empty($request['message']['text'])) {
      $update['message'] = $request['message']['text'];
    }

    if (isset($request['callback_query']) && !empty($request['callback_query'])) {
      $update['callback_query'] = $request['callback_query'];
    }

    if (isset($request['message']['photo']) && !empty($request['message']['photo'])) {
      $update['photo'] = $request['message']['photo'];
    }

    if (isset($request['message']['document']) && !empty($request['message']['document'])) {
      $update['document'] = $request['message']['document'];
    }

    if (isset($request['signature']) && !empty($request['signature'])) {
      $update['signature'] = $request['signature'];
    }

    $update['data'] = $request;
    // $this->logger->get('tis_service_upd')->info(print_r($update, TRUE));
    return $update;
  }

  /**
   * Function to get Telegram bot from config.
   */
  private function getBot() {
    $configFactory = $this->settings;

    $botKey = $configFactory->get('http_api');

    $requestFactory = new RequestFactory();
    $streamFactory = new StreamFactory();
    $client = new Client();

    $bot = NULL;

    if (empty($botKey)) {
      return $bot;
    }

    $apiClient = new ApiClient($requestFactory, $streamFactory, $client);
    try {
      $bot = new BotApi($botKey, $apiClient, new BotApiNormalizer());
    }
    catch (ResponseException $re) {
      // \Drupal::logger('tis')->error($re->getMessage());
      $this->logger->get('tis')->error($re->getMessage());
    }
    return $bot;
  }

  /**
   * Function to made a call to api metodhs.
   */
  public function call($method) {
    $bot = $this->getBot();

    if ($bot !== NULL) {
      try {
        $callResponse = $bot->call($method);
        if (is_array($callResponse)) {
          return $callResponse;
        }
      }
      catch (ResponseException $re) {
        $this->logger->get('tis')->error($re->getMessage());
      }
    }

    return NULL;
  }

  /**
   * Function to get telegram updates.
   */
  public function updates() {
    $data = [];

    $method = GetUpdatesMethod::create();
    $callResponse = $this->call($method);

    if (is_array($callResponse)) {
      foreach ($callResponse as $line) {
        $item = json_decode(json_encode((array) $line), TRUE);
        $data[] = [
          $item['message']['message_id'],
          isset($item['message']['from']) ? str_replace(',', ', ', json_encode((array) $item['message']['from'], TRUE)) : '{}',
          isset($item['message']['chat']) ? str_replace(',', ', ', json_encode((array) $item['message']['chat'], TRUE)) : '{}',
          isset($item['message']['date']) ? str_replace(',', ', ', json_encode((array) $item['message']['date'], TRUE)) : '{}',
          isset($item['message']['text']) ? str_replace(',', ', ', json_encode((array) $item['message']['text'], TRUE)) : '{}',
        ];
      }
    }

    return $data;
  }

  /**
   * Function to send message to group Using SendMessageMethod.
   */
  public function send($message, $chatId, $replyMarkup = NULL, $removeKeyboard = TRUE) {
    $bot = $this->getBot();
    $fields = [
      'parseMode' => HasParseModeVariableInterface::PARSE_MODE_HTML,
    ];

    if ($replyMarkup !== NULL) {
      $fields['replyMarkup'] = $replyMarkup;
    }
    else {
      if ($removeKeyboard) {
        $replyKeyboardRemove = new ReplyKeyboardRemoveType();
        $replyKeyboardRemove->removeKeyboard = TRUE;
        $fields['replyMarkup'] = $replyKeyboardRemove;
      }
    }

    // $this->logger->get('tis_send')->info(print_r($fields, TRUE));
    $method = SendMessageMethod::create($chatId, $message, $fields);

    if (!empty($chatId) && !empty($message) && $bot !== NULL) {
      try {
        $bot->send($method);
      }
      catch (ResponseException $re) {
        $this->logger->get('tis_send')->error($re->getMessage());
      }
    }

  }

  /**
   * Function to set telegram webhook for receive updates.
   */
  public function getChatId($udpate) {
    if (is_array($udpate)) {
      if (isset($udpate['data']['message']['chat']['id'])) {
        return $udpate['data']['message']['chat']['id'];
      }
    }
    else {
      return NULL;
    }
  }

  /**
   * Function to set telegram webhook for receive updates.
   */
  public function getChatIdForGroups($channel = NULL) {
    $configFactory = $this->settings;

    if ($channel !== NULL) {
      $key = 0;
      while ($configFactory->get('chat_' . $key . '_groups') !== NULL) {
        $groups = explode(',', $configFactory->get('chat_' . $key . '_groups'));
        if (in_array($channel, $groups)) {
          return $configFactory->get('chat_' . $key . '_id');
        }
        $key++;
      }
    }
  }

  /**
   * Function to set telegram webhook for receive updates.
   */
  public function setWebhook() {
    $configFactory = $this->settings;
    $webhook = $configFactory->get('webhook');

    $botResponse = [];

    if (!empty($webhook)) {
      $method = SetWebhookMethod::create($webhook);
      $botResponse = $this->call($method);
    }
    // $this->logger->get('tis_setwebhook')
    // ->info($webhook . ' - ' . print_r($botResponse, TRUE));
    return $botResponse;
  }

  /**
   * Function to delete telegram webhook url.
   */
  public function deleteWebhook() {
    $method = DeleteWebhookMethod::create(['dropPendingUpdates' => TRUE]);
    $callResponse = $this->call($method);
    // $this->logger->get('tis')->info(print_r($callResponse, TRUE));
    return $callResponse;
  }

  /**
   * Function to register commands avaliable for users.
   */
  public function registerCommands($commands) {

    if (empty($commands)) {
      return NULL;
    }

    $botCommands = [];

    foreach ($commands as $command) {
      $botCommands[] = BotCommandType::create($command['command'], $command['description']);
    }

    $method = SetMyCommandsMethod::create($botCommands);
    $botResponse = $this->call($method);
    // $this->logger->get('tis')->info(print_r($botResponse, TRUE));
    return $botResponse;
  }

  /**
   * Function to delete telegram webhook url.
   */
  public function getBotCommands() {
    $method = GetMyCommandsMethod::create();
    $callResponse = $this->call($method);
    // $this->logger->get('tis')->info(print_r($callResponse, TRUE));
    return $callResponse;
  }

  /**
   * Function to delete telegram webhook url.
   */
  public function donwloadFile($fileInTelegramServers) {
    // $botKey = $this->settings->get('http_api');
    $bot = $this->getBot();

    $method = GetFileMethod::create($fileInTelegramServers['file_id']);
    $fileInTelegram = $bot->getFile($method);
    // $this->logger->get('tis')->
    // info('file: ' . print_r($fileInTelegram, TRUE));
    $file = new FileType();
    $file->filePath = $fileInTelegram->filePath;

    $fileUrl = $bot->getAbsoluteFilePath($file);
    // $this->logger->get('tis_filepath')->info(print_r($fileUrl, TRUE));
    $fileName = '';
    if (!isset($fileInTelegramServers['file_name'])) {
      $pathParts = explode('/', $fileUrl);
      $fileName = end($pathParts);
    }
    else {
      $fileName = $fileInTelegramServers['file_name'];
    }

    $localFileId = $this->donwloadFileFromTelegram($fileUrl, $fileName);
    // $this->logger->get('tis_localfile')->info(print_r($localFileId, TRUE));
    return $localFileId;
  }

  /**
   * Function to delete dowloand and store localy a file from telegram.
   */
  public function donwloadFileFromTelegram($fileUrl, $fileName) {
    $client = $this->client->fromOptions([
      // 'base_uri' => 'https://api.telegram.org/',
    ]);

    try {
      $response = $client->get($fileUrl);
      // $result = Json::decode($response->getBody()
      // ->getContents());
      // $this->logger->get('tis_httpclient')
      // ->info(print_r($response->getBody(), TRUE));
      $fileDirectory = 'private://contract-assets-from-telegram/';
      $this->fileSystem->prepareDirectory(
        $fileDirectory,
        $this->fileSystem::CREATE_DIRECTORY | $this->fileSystem::MODIFY_PERMISSIONS
      );
      /** @var \Drupal\file\Entity\File $file */
      $file = file_save_data($response->getBody()->getContents(), $fileDirectory . $fileName, $this->fileSystem::EXISTS_REPLACE);
      return $file->id();
    }
    catch (ClientException $exception) {
      $this->logger->get('tis')->error($exception->getMessage());
      return FALSE;
    }
  }

  /**
   * Function to send an answer callback query.
   */
  public function sendAnswerCallbackQuery(AnswerMethodAliasInterface $answer) {
    // $bot = $this->getBot();
    // $botResponse = $bot->answer($answer);
    // $this->logger->get('tis_send_answer')
    // ->innfo(print_r($botResponse, TRUE));
  }

  /**
   * Function to send an answer callback query.
   */
  public function sendGame($method) {
    $bot = $this->getBot();
    $bot->call($method);
  }

  /**
   * Function to convert an array in html text with line breaks.
   */
  public function arrayToHtml($lines) {
    $outputHtml = '';
    if (is_array($lines)) {
      foreach ($lines as $line) {
        $outputHtml .= '- ' . $line . PHP_EOL;
      }
    }
    return $outputHtml;
  }

  /**
   * Function to send an answer callback query.
   */
  public function getFromUser($update) {
    if (isset($update['data']['message']['from'])) {
      $userFrom = $update['data']['message']['from'];
      return $userFrom;
    }
    return NULL;
  }

  /**
   * Function to send an answer callback query.
   */
  public function getChat($update) {
    if (isset($update['data']['message']['chat'])) {
      $userFrom = $update['data']['message']['chat'];
      return $userFrom;
    }
    return NULL;
  }

  /**
   * Function to send an answer callback query.
   */
  public function getUserInfo(int $userId, $chatId) {
    if (empty($userId) || empty($chatId)) {
      return '';
    }
    $bot = $this->getBot();
    $userInfo = $bot->getChatMember(GetChatMemberMethod::create($chatId, $userId));
    return $userInfo;
  }

}
