<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a 'PostGroupBlock' block.
 *
 * @Block(
 *   id = "post_group_block",
 *   admin_label = @Translation("Post on group block"),
 * )
 */
class PostGroupBlock extends PostBlock {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    FormBuilderInterface $form_builder,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $current_user,
      $form_builder,
      $module_handler
    );

    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'group';
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $group = _social_group_get_current_group();
    $cache_contexts = [
      'route.group',
      'user.group_permissions',
    ];
    $access = AccessResult::forbidden()->addCacheContexts($cache_contexts);

    if ($group instanceof GroupInterface) {
      if (
        $group->hasPermission('add post entities in group', $account) &&
        (
          $account->hasPermission('add post entities') ||
          $account->hasPermission("add {$this->bundle} post entities")
        )
      ) {
        $membership = $group->getMember($account);
        $context = [];
        if ($membership) {
          $group_content = $membership->getGroupContent();
          $context = ['group_content' => $group_content];
          if (
            $group->hasField('field_group_posts_enabled') &&
            !$group->get('field_group_posts_enabled')->isEmpty() &&
            !(bool) $group->get('field_group_posts_enabled')->getString() &&
            !$group->hasPermission('edit group', $account)
          ) {
            return AccessResult::forbidden()->addCacheContexts($cache_contexts)->addCacheTags(['group:' . $group->id()]);
          }
        }
        $access = $this->entityTypeManager
          ->getAccessControlHandler($this->entityType)
          ->createAccess($this->bundle, $account, $context, TRUE)
          ->addCacheContexts($cache_contexts)
          ->addCacheTags(['group:' . $group->id()]);
      }
    }

    // By default, the block is not visible.
    return $access;
  }

}
