<?php

namespace Drupal\hablame\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hablame\Service\HablameService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure hablame settings for this site.
 */
class StatusForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Variable that store the Hablame Service.
   *
   * @var \Drupal\hablame\Service\HablameService
   */
  protected $hablame;

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
   * @param \Drupal\hablame\Service\HablameService $hablame
   *   The Hablame Service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, HablameService $hablame) {
    $this->moduleHandler = $moduleHandler;
    $this->hablame = $hablame;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('hablame.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hablame_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $module_path = $this->moduleHandler->getModule('hablame')->getPath();

    $ymlFormFields = Yaml::decode(file_get_contents($module_path . '/assets/yml/form/hablame.status.form.yml'));
    foreach ($ymlFormFields as $key => $field) {
      $form[$key] = $field;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $smsId = $form_state->getValue('smsid');

    if (!empty($smsId) && !empty($message)) {
      $result = $this->hablame->getStatus($smsId);
      if ($result !== FALSE) {
        $this->messenger()->addMessage($this->t('Status consultado con exito. @result', [
          '@result' => print_r($result, TRUE),
        ]));
      }
      else {
        $this->messenger()->addMessage($this->t('Error al consultar el status.'));
      }
    }
  }

}
