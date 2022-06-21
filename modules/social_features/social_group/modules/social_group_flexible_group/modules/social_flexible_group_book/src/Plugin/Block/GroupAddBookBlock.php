<?php

namespace Drupal\social_flexible_group_book\Plugin\Block;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a 'GroupAddBookBlock' block.
 *
 * @Block(
 *  id = "group_add_book_block",
 *  admin_label = @Translation("Group add book block"),
 * )
 */
class GroupAddBookBlock extends BlockBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account): AccessResultInterface {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = _social_group_get_current_group();

    if ($group instanceof GroupInterface) {
      if ($group->hasPermission('create group_node:book entity', $account)) {
        return AccessResult::allowed();
      }
    }

    // Full access to node operations.
    if ($account->hasPermission('bypass node access')) {
      return AccessResult::allowed();
    }

    // By default, the block is not visible.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $build = [];
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = _social_group_get_current_group();

    if ($group instanceof GroupInterface) {
      $url = Url::fromRoute('entity.group_content.create_form',
        [
          'group' => $group->id(),
          'plugin_id' => 'group_node:book',
        ]
      );

      $link_options['attributes']['class'] = [
        'btn',
        'btn-primary',
        'btn-raised',
        'waves-effect',
        'brand-bg-primary',
      ];
      $url->setOptions($link_options);

      $build['content'] = Link::fromTextAndUrl($this->t('Create Book'), $url)
        ->toRenderable();

      // Cache.
      $build['#cache']['contexts'][] = 'url.path';
      $build['#cache']['tags'][] = 'group:' . $group->id();
    }

    return $build;
  }

}
