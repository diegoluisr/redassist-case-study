<?php

namespace Drupal\hablame\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines wkhtmltopdf form configuration.
 */
class SettingsConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'hablame.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hablame_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hablame.settings');
    $form['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('ID gotten from Hablame.co'),
      '#required' => 'TRUE',
      '#default_value' => $config->get('account'),
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret (API Key)'),
      '#description' => $this->t('API Key string gotten from Hablame.co'),
      '#required' => 'TRUE',
      '#default_value' => $config->get('api_key'),
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token (Gotten via email)'),
      '#description' => $this->t('token to generate and validate'),
      '#required' => 'TRUE',
      '#default_value' => $config->get('token'),
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

    $this->configFactory->getEditable('hablame.settings')
      ->set('account', $form_state->getValue('account'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('token', $form_state->getValue('token'))
      ->set('test', boolval($form_state->getValue('test')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
