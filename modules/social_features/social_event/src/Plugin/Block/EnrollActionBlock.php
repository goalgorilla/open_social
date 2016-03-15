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


    return array(
      'enroll_action_form' => $form
    );
  }

}
