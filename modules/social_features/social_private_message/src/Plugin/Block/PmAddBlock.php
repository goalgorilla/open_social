<?php

namespace Drupal\social_private_message\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a 'PmAddBlock' block.
 *
 * @Block(
 *  id = "pm_add_block",
 *  admin_label = @Translation("Private Message add block"),
 * )
 */
class PmAddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account) {
    if (
      $account->hasPermission('use private messaging system') &&
      $account->hasPermission('create private messages thread')
    ) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $url = Url::fromRoute('private_message.private_message_create');
    $link_options = [
      'attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'btn-raised',
          'brand-bg-primary',
        ],
      ],
    ];
    $url->setOptions($link_options);

    $build['content'] = Link::fromTextAndUrl(t('New message'), $url)
      ->toRenderable();

    return $build;
  }

}
