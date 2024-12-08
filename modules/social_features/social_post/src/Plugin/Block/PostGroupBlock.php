<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\CurrentGroupService;

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
    ModuleHandlerInterface $module_handler,
    CurrentRouteMatch $route_match,
    CurrentGroupService $current_group_service,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $current_user,
      $form_builder,
      $module_handler,
      $route_match,
      $current_group_service,
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
          $group_content = $membership->getGroupRelationship();
          $context = ['group_content' => $group_content];
          if (
            $group->hasField('field_group_posts_enabled') &&
            !$group->get('field_group_posts_enabled')->isEmpty() &&
            !$group->get('field_group_posts_enabled')->getString() &&
            !$group->hasPermission('edit group', $account)
          ) {
            return AccessResult::forbidden()->addCacheContexts($cache_contexts)->addCacheTags(['group:' . $group->id()]);
          }
        }
        /** @var \Drupal\Core\Access\AccessResult $access */
        $access = $this->entityTypeManager
          ->getAccessControlHandler($this->entityType)
          ->createAccess($this->bundle, $account, $context, TRUE);
        $access = $access
          ->addCacheContexts($cache_contexts)
          ->addCacheTags(['group:' . $group->id()]);
      }
    }

    // By default, the block is not visible.
    return $access;
  }

}
