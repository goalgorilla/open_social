<?php

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupType;

/**
 * Provides a 'GroupAddBlock' block.
 *
 * @Block(
 *  id = "group_add_block",
 *  admin_label = @Translation("Group add block"),
 * )
 */
class GroupAddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account) {
    $current_user = \Drupal::currentUser();
    $route_user_id = \Drupal::routeMatch()->getParameter('user');

    // Show this block only on current user Groups page.
    $can_create_groups = FALSE;
    foreach (GroupType::loadMultiple() as $group_type) {
      $permissions = 'create ' . $group_type->id() . ' group';
      if ($account->hasPermission($permissions)) {
        $can_create_groups = TRUE;
        break;
      }
    }
    if ($current_user->id() == $route_user_id && $can_create_groups) {
      return AccessResult::allowed();
    }

    // By default, the block is not visible.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // TODO: Add caching when closed groups will be added.
    $url = Url::fromRoute('entity.group.add_page');
    $link_options = [
      'attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'btn-raised',
          'waves-effect',
          'brand-bg-primary',
        ],
      ],
    ];
    $url->setOptions($link_options);

    $build['content'] = Link::fromTextAndUrl(t('Add a group'), $url)
      ->toRenderable();

    return $build;
  }

}
