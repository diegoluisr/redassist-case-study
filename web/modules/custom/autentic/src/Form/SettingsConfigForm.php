<?php

namespace Drupal\autentic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines wkhtmltopdf form configuration.
 */
class SettingsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'autentic.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autentic_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autentic.settings');
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('ID gotten from AutenTIC'),
      '#required' => 'TRUE',
      '#default_value' => $config->get('client_id'),
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#description' => $this->t('Secret string gotten from AutenTIC'),
      '#required' => 'TRUE',
      '#default_value' => $config->get('client_secret'),
    ];
    $form['otp_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('OTP Token'),
      '#description' => $this->t('OTP token to generate and validate'),
      '#required' => 'TRUE',
      '#default_value' => $config->get('otp_token'),
    ];
    $form['otp_hours'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OPT Time'),
      '#description' => $this->t('Enter the amount of time in hours that the validation of the OTP code will last.'),
      '#required' => 'TRUE',
      '#attributes' => [
        ' type' => 'number',
      ],
      '#default_value' => $config->get('otp_hours'),
    ];
    $form['max_intents'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 10,
      '#step' => 1,
      '#title' => $this->t('OTP max intents'),
      '#description' => $this->t('OTP max intents to validate autorization (recommended: 3).'),
      '#required' => 'TRUE',
      '#default_value' => $config->get('max_intents'),
    ];
    $form['test'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#description' => $this->t('Must use test URL paths?'),
      '#default_value' => $config->get('test'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('autentic.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('otp_token', $form_state->getValue('otp_token'))
      ->set('otp_hours', $form_state->getValue('otp_hours'))
      ->set('max_intents', intval($form_state->getValue('max_intents')))
      ->set('test', boolval($form_state->getValue('test')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
