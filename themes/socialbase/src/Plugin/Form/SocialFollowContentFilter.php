<?php

namespace Drupal\socialbase\Plugin\Form;

use Drupal\bootstrap\Plugin\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @ingroup plugins_form
 *
 * @BootstrapForm("social_follow_content_filter")
 */
class SocialFollowContentFilter extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alterForm($form, $form_state, $form_id);

    $form['actions']['#attributes']['class'][] = 'views-exposed-form__actions';

    $form['actions']['submit']['#leave-class'] = TRUE;

    $classes = [
      'submit' => [
        'button--default',
        'waves-effect',
        'waves-btn',
      ],
      'reset' => [
        'button',
        'button--flat',
        'js-form-submit',
        'form-submit',
        'btn',
        'js-form-submit',
        'btn-flat',
        'waves-effect',
        'waves-btn',
      ],
    ];

    foreach ($classes as $button => $class) {
      if (!isset($form['actions'][$button]['#attributes']['class'])) {
        $form['actions'][$button]['#attributes']['class'] = [];
      }

      $form['actions'][$button]['#attributes']['class'] = array_merge(
        $form['actions'][$button]['#attributes']['class'],
        $class
      );
    }
  }

}
