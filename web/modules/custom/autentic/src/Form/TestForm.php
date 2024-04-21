<?php

namespace Drupal\autentic\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\autentic\Service\AutenticService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure autentic settings for this site.
 */
class TestForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Variable that store the Autentic Service.
   *
   * @var \Drupal\autentic\Service\AutenticService
   */
  protected $autentic;

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
   * @param \Drupal\autentic\Service\AutenticService $autentic
   *   The Autentic Service.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    AutenticService $autentic
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->autentic = $autentic;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('autentic.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autentic_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $module_path = $this->moduleHandler->getModule('autentic')->getPath();

    $ymlFormFields = Yaml::decode(file_get_contents($module_path . '/assets/yml/form/autentic.test.yml'));
    foreach ($ymlFormFields as $key => $field) {
      $form[$key] = $field;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $email = $form_state->getValue('email');
    $queue = $form_state->getValue('queue');

    if (!empty($email)) {
      if ($queue) {
        // $this->messenger()->addMessage(
        // $this->t('Mensaje guardado en cola de envios.'));
        // $queue = \Drupal::queue('autentic_send_message_queue');
        // $queue->createQueue();
        // $queue->createItem([
        // 'phone' => $phone,
        // 'message' => $message,
        // ]);
      }
      else {
        $response = $this->autentic->generateEmailOtp($email);
        if ($response !== FALSE) {
          $this->messenger()->addMessage($this->t('Mensaje enviado con exito. @response', [
            '@response' => print_r($response, TRUE),
          ]));
        }
        else {
          $this->messenger()->addMessage($this->t('Error al solicitar el OTP.'));
        }
      }
    }
  }

}
