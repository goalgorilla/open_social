<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_bulk_operations\Form\ConfirmAction;

/**
 * Event action execution confirmation form.
 */
class EventEnrollmentConfirmActionForm extends ConfirmAction {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {
    $form = parent::buildForm($form, $form_state, $view_id, $display_id);

    if (isset($form['list'])) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $item */
      foreach ($form['list']['#items'] as &$item) {
        $item = $item->getArguments()['@name'];
      }
    }

    return $form;
  }

}
