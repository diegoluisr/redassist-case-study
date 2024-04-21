<?php

namespace Drupal\tis\Controller;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\tis\Service\TelegramAnswersKeyboard;
use Drupal\tis\Service\TelegramCommands;
use Drupal\tis\Service\TelegramProcessFiles;
use Drupal\tis\Service\TelegramProcessInputData;
use Drupal\tis\Service\TelegramService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the Example module.
 */
class SignatureApp extends ControllerBase {

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
   */
  public function __construct(
    ModuleHandler $moduleHandler,
    LoggerChannelFactoryInterface $loggerInterface,
    TelegramService $tisTelegram,
    TelegramCommands $telegramCommands,
    TelegramAnswersKeyboard $telegramAnswersKeyboard,
    TelegramProcessInputData $telegramProcessInputData,
    TelegramProcessFiles $telegramProcessFiles
    ) {
    $this->moduleHandler = $moduleHandler;
    $this->logger = $loggerInterface->get('tis_signatureapp');
    $this->tisTelegram = $tisTelegram;
    $this->telegramCommands = $telegramCommands;
    $this->telegramAnswersKeyboard = $telegramAnswersKeyboard;
    $this->telegramProcessInputData = $telegramProcessInputData;
    $this->telegramProcessFiles = $telegramProcessFiles;
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
    );
  }

  /**
   * Returns a simple page.
   */
  public function showSignatureForm() {
    $module_path = $this->moduleHandler->getModule('tis')->getPath();

    $page = [];
    $ymlFormFields = Yaml::decode(file_get_contents($module_path . '/assets/yml/form/tis.signaturetest.form.yml'));
    foreach ($ymlFormFields as $key => $field) {
      $page[$key] = $field;
    }
    return $page;
  }

}
