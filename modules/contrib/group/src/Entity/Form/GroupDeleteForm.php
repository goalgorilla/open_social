<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Form\GroupDeleteForm.
 */

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a group.
 */
class GroupDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the group %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    return new Url('entity.group.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::logger('group')->notice('@type: deleted %title.', [
      '@type' => $this->entity->bundle(),
      '%title' => $this->entity->label(),
    ]);
    $form_state->setRedirect('entity.group.collection');
  }

}
