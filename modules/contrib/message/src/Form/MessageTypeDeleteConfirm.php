<?php

/**
 * @file
 * Contains \Drupal\message\Form\MessageTypeDeleteConfirm.
 */

namespace Drupal\message\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for message type deletion.
 */
class MessageTypeDeleteConfirm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the message type %type?', ['%type' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $t_args = ['%name' => $this->entity->label()];
    drupal_set_message(t('The message type %name has been deleted.', $t_args));
    $this->logger('content')->notice('Deleted message type %name', $t_args);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('message.overview_types');
  }
}
