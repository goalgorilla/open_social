<?php

namespace Drupal\social_book\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'GroupAddBookBlock' block.
 *
 * @Block(
 *  id = "group_add_book_block",
 *  admin_label = @Translation("Group add book block"),
 * )
 */
class GroupAddBookBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account) {
    if (\Drupal::moduleHandler()->moduleExists('social_group')) {
      $group = _social_group_get_current_group();
    }
    else {
      $group = NULL;
    }

    if (is_object($group)) {
      if ($group->hasPermission('create group_node:book entity', $account)&& $account->hasPermission("create book content")) {
        if ($group->getGroupType()->id() === 'public_group') {
          $config = \Drupal::config('entity_access_by_field.settings');
          if ($config->get('disable_public_visibility') === 1 && !$account->hasPermission('override disabled public visibility')) {
            return AccessResult::forbidden();
          }
        }
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

    if (\Drupal::moduleHandler()->moduleExists('social_group')) {
      $group = _social_group_get_current_group();
    }
    else {
      $group = NULL;
    }

    if (is_object($group)) {
      $url = Url::fromUserInput("/group/{$group->id()}/content/create/group_node:book");

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

      $build['content'] = Link::fromTextAndUrl(t('Create book page'), $url)->toRenderable();

      // Cache.
      $build['#cache']['contexts'][] = 'url.path';
      $build['#cache']['tags'][] = 'group:' . $group->id();
    }

    return $build;
  }

}
