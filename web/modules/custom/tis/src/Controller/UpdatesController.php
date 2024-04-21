<?php

namespace Drupal\tis\Controller;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\tis\Service\TelegramService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the Example module.
 */
class UpdatesController extends ControllerBase {

  /**
   * Variable that store the module handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Variable that store the tis Service.
   *
   * @var \Drupal\tis\Service\TelegramService
   */
  protected $tisTelegram;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler.
   * @param \Drupal\tis\Service\TelegramService $tisTelegram
   *   The tis Telegram service.
   */
  public function __construct(
    ModuleHandler $moduleHandler,
    TelegramService $tisTelegram
    ) {
    $this->moduleHandler = $moduleHandler;
    $this->tisTelegram = $tisTelegram;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('telegram.service')
    );
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function getUpdates() {
    $module_path = $this->moduleHandler->getModule('tis')->getPath();

    $page = [];
    $ymlFormFields = Yaml::decode(file_get_contents($module_path . '/assets/yml/page/tis.updates.page.yml'));
    foreach ($ymlFormFields as $key => $field) {
      $page[$key] = $field;
    }

    // $page['table']['#rows'] = \Drupal::service('tis.telegram')->updates();
    $page['table']['#rows'] = $this->tisTelegram->updates();

    return $page;
  }

}
