<?php

namespace Drupal\tis\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tis\Service\TelegramService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure tis settings for this site.
 */
class TestForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Variable that store the Telegram Service.
   *
   * @var \Drupal\tis\Service\TelegramService
   */
  protected $telegram;

  /**
   * Variable that store the module handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\tis\Service\TelegramService $telegram
   *   The Telegram Service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, TelegramService $telegram) {
    $this->moduleHandler = $moduleHandler;
    $this->telegram = $telegram;
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tis_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $module_path = $this->moduleHandler->getModule('tis')->getPath();

    $ymlFormFields = Yaml::decode(file_get_contents($module_path . '/assets/yml/form/tis.test.form.yml'));
    $config = $this->config(TisSettingsForm::SETTINGS);
    foreach ($ymlFormFields as $key => $field) {
      $form[$key] = $field;
    }

    $key = 0;
    $groups = [];
    while ($config->get('chat_' . $key . '_id') !== NULL) {
      foreach (explode(',', $config->get('chat_' . $key . '_groups')) as $group) {
        $groups[$group] = $group;
      }
      $key++;
    }

    $form['group']['#options'] = $groups;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $group = $form_state->getValue('group');
    $message = $form_state->getValue('message');

    if (!empty($group) && !empty($message)) {
      $this->telegram->send($message, $group);
      $this->messenger()->addMessage($this->t('Mensaje enviado con exito.'));
    }
  }

}
