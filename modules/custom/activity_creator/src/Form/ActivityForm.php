<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Form\ActivityForm.
 */

namespace Drupal\activity_creator\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Activity edit forms.
 *
 * @ingroup activity_creator
 */
class ActivityForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\activity_creator\Entity\Activity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Activity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Activity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.activity.canonical', ['activity' => $entity->id()]);
  }

}
