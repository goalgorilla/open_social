<?php

namespace Drupal\social_event_managers\Plugin;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\GroupContentAccessControlHandler;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;

/**
 * Provides access control for Event GroupContent entities.
 */
class EventsGroupContentAccessControlHandler extends GroupContentAccessControlHandler {

  /**
   * The plugin's permission provider.
   *
   * @var \Drupal\group\Plugin\GroupContentPermissionProviderInterface
   */
  protected $permissionProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    // We only care about Update (/edit) of the Event content.
    if ($operation !== 'update') {
      return parent::entityAccess($entity, $operation, $account, $return_as_object);
    }

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content');
    $group_contents = $storage->loadByEntity($entity);

    // Filter out the content that does not use this plugin.
    foreach ($group_contents as $id => $group_content) {
      // @todo Shows the need for a plugin ID base field.
      $plugin_id = $group_content->getContentPlugin()->getPluginId();
      if ($plugin_id !== $this->pluginId) {
        unset($group_contents[$id]);
      }
    }

    // If this plugin is not being used by the entity, we have nothing to say.
    if (empty($group_contents)) {
      return AccessResult::neutral();
    }

    // We need to determine if the user has access based on group permissions.
    $group_based_access = parent::entityAccess($entity, $operation, $account, $return_as_object);

    // Only when the access result is False we need to override,
    // when a user already has access based on Group relation we're good.
    if (!$group_based_access instanceof AccessResultForbidden) {
      return $group_based_access;
    }

    // Based on the EventManager access we can determine if a user
    // is the owner, or an event manager/organizer and give out
    // permissions.
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $group_content->getEntity();
      $result = SocialEventManagersAccessHelper::getEntityAccessResult($node, $operation, $account);
      if ($result->isAllowed()) {
        break;
      }
    }

    // If we did not allow access, we need to explicitly forbid access to avoid
    // other modules from granting access where Group promised the entity would
    // be inaccessible.
    if (!$result->isAllowed()) {
      $result = AccessResult::forbidden()->addCacheContexts(['user.group_permissions']);
    }
    $result->addCacheContexts(['user']);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
