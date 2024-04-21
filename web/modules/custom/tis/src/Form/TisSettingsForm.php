<?php

namespace Drupal\tis\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tis\Service\TelegramService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure tis settings for this site.
 */
class TisSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'tis.settings';

  /**
   * Variable that store the module handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Variable that store the Telegram Service.
   *
   * @var \Drupal\tis\Service\TelegramService
   */
  protected $telegram;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\tis\Service\TelegramService $telegram
   *   The Telegram Service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
    TelegramService $telegram
  ) {
    parent::__construct($configFactory);
    $this->moduleHandler = $moduleHandler;
    $this->telegram = $telegram;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('telegram.service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tis_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $module_path = $this->moduleHandler->getModule('tis')->getPath();

    $ymlFormFields = Yaml::decode(file_get_contents($module_path . '/assets/yml/form/tis.setting.form.yml'));
    $config = $this->configFactory->get(static::SETTINGS);
    foreach ($ymlFormFields as $key => $field) {
      $form[$key] = $field;
      if ($config->get($key) != NULL) {
        $form[$key]['#default_value'] = $config->get($key);
      }
    }

    $key = 0;
    if (isset($form['chats']['#repeat'])) {
      while ($config->get('chat_' . $key . '_id') !== NULL && $config->get('chat_' . $key . '_id') !== '') {
        $form['chats'][$key] = $form['chats']['#repeat'];
        $form['chats'][$key]['id']['#default_value'] = $config->get('chat_' . $key . '_id');
        $form['chats'][$key]['groups']['#default_value'] = $config->get('chat_' . $key . '_groups');
        $key++;
      }
      $form['chats'][] = $form['chats']['#repeat'];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $config = $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('http_api', $form_state->getValue('http_api'));

    if (!empty($form_state->getValue('webhook'))) {
      $config->set('webhook', $form_state->getValue('webhook'));
    }

    if (!empty($form_state->getValue('signature_webapp_url'))) {
      $config->set('signature_webapp_url', $form_state->getValue('signature_webapp_url'));
    }

    if (!empty($form_state->getValue('support_url'))) {
      $config->set('support_url', $form_state->getValue('support_url'));
    }

    if (!empty($form_state->getValue('terms_url'))) {
      $config->set('terms_url', $form_state->getValue('terms_url'));
    }

    if (!empty($form_state->getValue('website_url'))) {
      $config->set('website_url', $form_state->getValue('website_url'));
    }

    foreach ($form_state->getValue('chats') as $key => $group) {
      $config->set('chat_' . $key . '_id', $group['id']);
      $config->set('chat_' . $key . '_groups', $group['groups']);
    }

    $config->save();
    // Register webhook on telegram.
    $this->telegram->setWebhook();

    parent::submitForm($form, $form_state);
  }

}
