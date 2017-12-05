<?php

namespace Drupal\social_private_message\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'PmAddBlock' block.
 *
 * @Block(
 *  id = "pm_add_block",
 *  admin_label = @Translation("PM add block"),
 * )
 */
class PmAddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowed();
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
