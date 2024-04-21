<?php

namespace Drupal\gsuite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines gsuite config form configuration.
 */
class SalesConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gsuite_sales.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gsuite_sales_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gsuite_sales.settings');
    $form['consolidated_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('consolidated sales report'),
      '#description' => $this->t('Insert the file ID of consolidated sales report.'),
      '#default_value' => $config->get('consolidated_id'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('gsuite_sales.settings')
      ->set('consolidated_id', $form_state->getValue('consolidated_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
