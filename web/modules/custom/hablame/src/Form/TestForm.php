<?php

namespace Drupal\hablame\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hablame\Service\HablameService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure hablame settings for this site.
 */
class TestForm extends FormBase {

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
   * The queue.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\hablame\Service\HablameService $hablame
   *   The Hablame Service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    HablameService $hablame,
    QueueFactory $queue
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->hablame = $hablame;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('hablame.service'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hablame_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $module_path = $this->moduleHandler->getModule('hablame')->getPath();

    $ymlFormFields = Yaml::decode(file_get_contents($module_path . '/assets/yml/form/hablame.test.form.yml'));
    foreach ($ymlFormFields as $key => $field) {
      $form[$key] = $field;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $phone = $form_state->getValue('phone');
    $message = $form_state->getValue('message');
    $queue = $form_state->getValue('queue');
    $otp = $form_state->getValue('otp');

    if (!empty($phone)) {
      if (!empty($message)) {
        if ($queue) {
          $this->messenger()->addMessage($this->t('Mensaje guardado en cola de envios.'));
          $queue = $this->queue->get('hablame_send_message_queue');
          $queue->createQueue();
          $queue->createItem([
            'phone' => $phone,
            'message' => $message,
          ]);
        }
        else {
          $this->messenger()->addMessage($this->t('Mensaje enviado con exito.'));
          $this->hablame->sendMessage($phone, $message);
        }
      }

      if (!empty($otp)) {
        if ($queue) {
          $queue = $this->queue->get('hablame_send_audio_otp_queue');
          $queue->createQueue();
          $queue->createItem([
            'phone' => $phone,
            'otp' => $otp,
          ]);
        }
        else {
          $this->hablame->audioOtp($otp, $phone);
        }

      }
    }
  }

}
