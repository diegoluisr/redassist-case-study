<?php

namespace Drupal\hablame\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines wkhtmltopdf form configuration.
 */
class ValidateOtpForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Cache status.
   *
   * @var bool
   */
  protected $cache = FALSE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The current pack stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The file storage backend.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   The curent pack stack.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandler $moduleHandler,
    CurrentPathStack $currentPath,
    Messenger $messenger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->currentPath = $currentPath;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('path.current'),
      $container->get('messenger'),
      $container->get('b2c.user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hablame_validate_otp';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $hash = NULL) {
    $form['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OTP Code (4 digits)'),
      '#description' => $this->t('ID gotten from Hablame.co'),
      '#required' => 'TRUE',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
