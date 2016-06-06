<?php

/**
 * @file
 * Contains \Drupal\social_event\Plugin\Block\EnrollActionBlock.
 */

namespace Drupal\social_event\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
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
    if ($route_name === "view.event_enrollments.view_enrollments" || $route_name === 'entity.node.canonical') {
      $node = \Drupal::service('current_route_match')->getParameter('node');
      if (!is_null($node) && !is_object($node)) {
        $node = node_load($node);
      }

      if (is_object($node) && $node->getType() === 'event') {
        // Retrieve the group and if there are groups respect group permission.
        $groups = $this->getGroups($node);
        if (!empty($groups)) {
          foreach ($groups as $group) {
            if ($group->hasPermission('enroll to events in groups', $account)) {
              return AccessResult::allowed();
            }
          }
        }
        else {
          // @TODO Always show the block when the user is already enrolled.
          return AccessResult::allowed();
        }
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

    $text = (string) t('You have enrolled for this event.');

    // Add extra text to
    if ($form['to_enroll_status']['#value'] === '0') {
      $render_array['feedback_user_has_enrolled'] = array(
        '#markup' => '<div><strong>' . $text . '</strong></div>',
      );
    }

    return $render_array;
  }

  /**
   * Get group object where event enrollment is posted in.
   *
   * Returns an array of Group Objects.
   *
   * @return array $groups
   */
  public function getGroups($node) {

    $groupcontents = GroupContent::loadByEntity($node);

    $groups = [];
    // Only react if it is actually posted inside a group.
    if (!empty($groupcontents)) {
      foreach ($groupcontents as $groupcontent) {
        /** @var \Drupal\group\Entity\GroupContent $groupcontent */
        $group = $groupcontent->getGroup();
        /** @var \Drupal\group\Entity\Group $group*/
        $groups[] = $group;
      }
    }

    return $groups;
  }

}
