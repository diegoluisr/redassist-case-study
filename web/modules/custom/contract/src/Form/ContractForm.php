<?php

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the contract entity edit forms.
 *
 * @ingroup contract
 */
class ContractForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\contract\Entity\Contract $entity */
    $entity = &$this->entity;
    $form = parent::buildForm($form, $form_state);

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    $status = parent::save($form, $form_state);

    if ($status == SAVED_UPDATED) {
      $this->messenger()
        ->addMessage($this->t('The contract %feed has been updated.', ['%feed' => $entity->toLink()->toString()]));
    }
    else {
      $this->messenger()
        ->addMessage($this->t('The contract %feed has been added.', ['%feed' => $entity->toLink()->toString()]));
    }

    // $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    $content_entity_id = $entity->getEntityType()->id();
    $form_state->setRedirect("entity.{$content_entity_id}.canonical", [$content_entity_id => $entity->id()]);
    return $status;
  }

}
