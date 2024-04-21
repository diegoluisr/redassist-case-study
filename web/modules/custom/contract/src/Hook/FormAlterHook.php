<?php

namespace Drupal\contract\Hook;

use Drupal\cookhook\Hook\Hook;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class detail.
 */
class FormAlterHook extends Hook {

  use StringTranslationTrait;

  /**
   * Function detail.
   */
  public function call(array $params = []) {

    // Params: &$form, FormStateInterface $form_state, $form_id .
    extract($params, EXTR_OVERWRITE | EXTR_REFS);

    if (in_array($form_id, [
      'contract_sale_add_form',
      'contract_sale_edit_form',
    ])) {
      $this->mediaContractAddForm($form, $form_state, $form_id);
    }
  }

  /**
   * Function to alter user_register_form.
   */
  private function mediaContractAddForm(&$form, FormStateInterface $form_state, $form_id) {
    $form['manifest']['widget'][0]['value']['#attributes']['data-yaml-editor'] = 'true';
  }

}
