<?php

namespace Drupal\ffmpeg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines ffmpeg form configuration.
 */
class SettingsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ffmpeg.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ffmpeg_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ffmpeg.settings');
    $form['binary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to binary'),
      '#description' => $this->t('Path to ffmpeg binary'),
      '#default_value' => $config->get('binary'),
    ];
    $form['target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target folder'),
      '#description' => $this->t('Where the files will be saved publicly.'),
      '#default_value' => $config->get('target'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('ffmpeg.settings')
      ->set('binary', $form_state->getValue('binary'))
      ->set('target', $form_state->getValue('target'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
