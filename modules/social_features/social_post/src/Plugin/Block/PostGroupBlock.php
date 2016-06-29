<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'PostGroupBlock' block.
 *
 * @Block(
 *  id = "post_group_block",
 *  admin_label = @Translation("Post on group block"),
 * )
 */
class PostGroupBlock extends PostBlock {

  public $entityType;
  public $bundle;
  public $formDisplay;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'group';
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $group = _social_group_get_current_group();

    if (is_object($group)) {
      if ($group->hasPermission('add post entities in group', $account)) {
        $membership = $group->getMember($account);
        $context = [];
        if (!empty($membership)) {
          $group_content = $membership->getGroupContent();
          $context = ['group_content' => $group_content];
        }
        return \Drupal::entityTypeManager()
          ->getAccessControlHandler($this->entityType)
          ->createAccess($this->bundle, $account, $context, TRUE);
      }
    }

    // By default, the block is not visible.
    return AccessResult::forbidden()->setCacheMaxAge(0);
  }


}
