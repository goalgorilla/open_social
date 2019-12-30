<?php

namespace Drupal\social_queue_storage\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QueueStorageEntityTypeForm.
 */
class QueueStorageEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $queue_storage_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $queue_storage_entity_type->label(),
      '#description' => $this->t("Label for the Queue storage entity type."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $queue_storage_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\social_queue_storage\Entity\QueueStorageEntityType::load',
      ],
      '#disabled' => !$queue_storage_entity_type->isNew(),
    ];
    /* You will need additional form elements for your custom properties. */
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $queue_storage_entity_type = $this->entity;
    $status = $queue_storage_entity_type->save();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Queue storage entity type.', [
          '%label' => $queue_storage_entity_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Queue storage entity type.', [
          '%label' => $queue_storage_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($queue_storage_entity_type->toUrl('collection'));
  }

}
