<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Event enrollment edit forms.
 *
 * @ingroup social_event
 */
class EventEnrollmentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Event enrollment.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Event enrollment.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.event_enrollment.canonical', ['event_enrollment' => $entity->id()]);

    return 0;
  }

}
