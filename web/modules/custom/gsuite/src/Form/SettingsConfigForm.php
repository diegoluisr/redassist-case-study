<?php

namespace Drupal\gsuite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\State\StateInterface;
use Drupal\gsuite\Service\ClientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines wkhtmltopdf form configuration.
 */
class SettingsConfigForm extends ConfigFormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The client factory.
   *
   * @var \Drupal\gsuite\Service\ClientFactory
   */
  protected $clientFactory;

  /**
   * The settings form constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \DruDrupal\gsuite\Service\ClientFactory $clientFactory
   *   Settings object.
   */
  public function __construct(
    StateInterface $state,
    ClientFactory $clientFactory
  ) {
    $this->state = $state;
    $this->clientFactory = $clientFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('gsuite.client.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gsuite.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gsuite_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $config = $this->config('gsuite.settings');
    $form['token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('GSuite Access Token'),
      '#description' => $this->t('Insert the JSON Token provided by Google here'),
      '#default_value' => $this->state->get('gsuite_token'),
      // '#attributes' => [
      //   'disabled' => 'disabled',
      // ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $redirect_url = new TrustedRedirectResponse($this->clientFactory->authorize());

    $form_state->setResponse($redirect_url);
  }

}
