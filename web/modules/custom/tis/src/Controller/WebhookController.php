<?php

namespace Drupal\tis\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\tis\Service\TelegramAnswersKeyboard;
use Drupal\tis\Service\TelegramCommands;
use Drupal\tis\Service\TelegramProcessFiles;
use Drupal\tis\Service\TelegramProcessInputData;
use Drupal\tis\Service\TelegramProcessSignature;
use Drupal\tis\Service\TelegramService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route responses for the Example module.
 */
class WebhookController extends ControllerBase {

  /**
   * Variable that store the module handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramService
   */
  protected $tisTelegram;

  /**
   * Variable that store the tis Commands Service.
   *
   * @var \Drupal\tis\Service\TelegramCommands
   */
  protected $telegramCommands;

  /**
   * Variable that store the tis answers keyboards Service.
   *
   * @var \Drupal\tis\Service\TelegramAnswersKeyboard
   */
  protected $telegramAnswersKeyboard;

  /**
   * Variable that store the tis process input data service.
   *
   * @var \Drupal\tis\Service\TelegramProcessInputData
   */
  protected $telegramProcessInputData;

  /**
   * Variable that store the tis process input data service.
   *
   * @var \Drupal\tis\Service\TelegramProcessFiles
   */
  protected $telegramProcessFiles;

  /**
   * Variable that store the tis process input data service.
   *
   * @var \Drupal\tis\Service\TelegramProcessSignature
   */
  protected $telegramProcessSignature;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerInterface
   *   The loggerInterface service.
   * @param \Drupal\tis\Service\TelegramService $tisTelegram
   *   The tis Telegram service.
   * @param \Drupal\tis\Service\TelegramCommands $telegramCommands
   *   The telegram commands service.
   * @param \Drupal\tis\Service\TelegramAnswersKeyboard $telegramAnswersKeyboard
   *   The telegram commands service.
   * @param \Drupal\tis\Service\TelegramProcessInputData $telegramProcessInputData
   *   The telegram process input data service.
   * @param \Drupal\tis\Service\TelegramProcessFiles $telegramProcessFiles
   *   The telegram process files data service.
   * @param \Drupal\tis\Service\TelegramProcessSignature $telegramProcessSignature
   *   The telegram process files data service.
   */
  public function __construct(
    ModuleHandler $moduleHandler,
    LoggerChannelFactoryInterface $loggerInterface,
    TelegramService $tisTelegram,
    TelegramCommands $telegramCommands,
    TelegramAnswersKeyboard $telegramAnswersKeyboard,
    TelegramProcessInputData $telegramProcessInputData,
    TelegramProcessFiles $telegramProcessFiles,
    TelegramProcessSignature $telegramProcessSignature
    ) {
    $this->moduleHandler = $moduleHandler;
    $this->logger = $loggerInterface->get('tis_webhook');
    $this->tisTelegram = $tisTelegram;
    $this->telegramCommands = $telegramCommands;
    $this->telegramAnswersKeyboard = $telegramAnswersKeyboard;
    $this->telegramProcessInputData = $telegramProcessInputData;
    $this->telegramProcessFiles = $telegramProcessFiles;
    $this->telegramProcessSignature = $telegramProcessSignature;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('logger.factory'),
      $container->get('telegram.service'),
      $container->get('telegram.commands'),
      $container->get('telegram.answerskeyboard'),
      $container->get('telegram.processinputdata'),
      $container->get('telegram.processfiles'),
      $container->get('telegram.processsignature'),
    );
  }

  /**
   * Returns a simple page.
   */
  public function processRequests(Request $request) {
    // Get important vars from request.
    $update = $this->tisTelegram->buildRequestVars($request->getContent());
    // $this->logger->info(print_r($update, TRUE));
    if (is_array($update)) {
      if (isset($update['message']) && !empty($update['message'])) {
        // Check and process commands.
        $this->telegramCommands->getCommandResponse($update);
        // Check and process input data.
        $this->telegramProcessInputData->processInputData($update);
      }
      // Check if is a documento or photo.
      if (
        (isset($update['photo']) && !empty($update['photo']))
        || (isset($update['document']) && !empty($update['document']))
      ) {
        $this->telegramProcessFiles->processFiles($update);
      }

      if (isset($update['signature']) && !empty($update['signature'])) {
        $this->telegramProcessSignature->processSignature($update);
      }

      if (isset($update['callback_query']) && !empty($update['callback_query'])) {
        $this->telegramAnswersKeyboard->getAnswerResponse($update);
      }
    }
    return new Response(
      Json::encode(['res' => 'ok']),
      200,
      ['Content-Type' => 'application/json']
    );
  }

}
