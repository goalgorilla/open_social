<?php

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'GroupAddEventBlock' block.
 *
 * @Block(
 *  id = "group_add_event_block",
 *  admin_label = @Translation("Group add event block"),
 * )
 */
class GroupAddEventBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account) {
    $group = _social_group_get_current_group();

    if (is_object($group)) {
      if ($group->hasPermission('create group_node:event entity', $account)&& $account->hasPermission('create event content')) {
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
    $build = [];

    $group = _social_group_get_current_group();

    if (is_object($group)) {
      $url = Url::fromRoute('entity.group_content.create_form', ['group' => $group->id(), 'plugin_id' => 'group_node:event']);
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

      $build['content'] = Link::fromTextAndUrl(t('Create Event'), $url)->toRenderable();

      // Cache.
      $build['#cache']['contexts'][] = 'url.path';
      $build['#cache']['tags'][] = 'group:' . $group->id();

    }

    return $build;
  }

}
