<?php

/**
 * @file
 * Contains \Drupal\social_event\Plugin\Block\EnrollActionBlock.
 */

namespace Drupal\social_event\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

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
   *
   * Custom access logic to display the block on the hero region for an event.
   */
  function blockAccess(AccountInterface $account) {
    $route_name = \Drupal::request()->get(RouteObjectInterface::ROUTE_NAME);
    if ($route_name === "view.enrollments.view_enrollments") {
      return AccessResult::allowed();
    }
    elseif ($route_name === 'entity.node.canonical') {
      $node = \Drupal::service('current_route_match')->getParameter('node');
      if (!is_null($node) && !is_object($node)) {
        $node = Node::load($node);
      }

      if (is_object($node) && $node->getType() === 'event') {
        return AccessResult::allowed();
      }
    }
    // By default, the block is not visible.
    return AccessResult::forbidden();
}

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
