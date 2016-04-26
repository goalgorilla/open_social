<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupJoinForm.
 */

namespace Drupal\group\Form;

use Drupal\group\Entity\Form\GroupContentForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for joining a group.
 */
class GroupJoinForm extends GroupContentForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['entity_id']['#access'] = FALSE;
    $form['group_roles']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Join group');
    return $actions;
  }

}
