<?php

namespace Drupal\social_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'EventAddBlock' block.
 *
 * @Block(
 *  id = "event_add_block",
 *  admin_label = @Translation("Event add block"),
 * )
 */
class EventAddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block only on current user Events page.
   */
  function blockAccess(AccountInterface $account) {
    $route_user_id = \Drupal::routeMatch()->getParameter('user');
    if ($account->id() == $route_user_id) {
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

    $url = Url::fromUserInput('/node/add/event');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'btn',
          'btn-primary',
          'btn-raised',
          'btn-block',
          'waves-effect',
          'waves-light',
        ),
      ),
    );
    $url->setOptions($link_options);

    $build['content'] = Link::fromTextAndUrl(t('Create Event'), $url)
      ->toRenderable();

    return $build;
  }

}
