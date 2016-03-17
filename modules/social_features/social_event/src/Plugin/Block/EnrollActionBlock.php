<?php

/**
 * @file
 * Contains \Drupal\social_event\Plugin\Block\EnrollActionBlock.
 */

namespace Drupal\social_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'EnrollActionBlock' block.
 *
 * @Block(
 *  id = "enroll_action_block",
 *  admin_label = @Translation("Enroll action block"),
 * )
 */
class EnrollActionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\social_event\Form\EnrollActionForm');

    $render_array = array(
      'enroll_action_form' => $form
    );

    // Add extra text to
    if ($form['to_enroll_status']['#value'] === '0') {
      $render_array['feedback_user_has_enrolled'] = array(
        '#markup' => '<div><b>You have enrolled to this event</b></div>',
      );
    }

    return $render_array;
  }

}
