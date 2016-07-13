<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Storage\GroupRoleStorage.
 */

namespace Drupal\group\Entity\Storage;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for group role entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group role entities based on user and group information.
 */
class GroupRoleStorage extends ConfigEntityStorage implements GroupRoleStorageInterface {

  /**
   * Static cache of altered group role IDs.
   *
   * @todo Perhaps we need to be able to clear this cache during runtime?
   *
   * @var array
   */
  protected $alteredIds = [];

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a GroupRoleStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, GroupMembershipLoaderInterface $membership_loader, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->membershipLoader = $membership_loader;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('group.membership_loader'),
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUserAndGroup(AccountInterface $account, GroupInterface $group, $include_implied = TRUE) {
    if (!isset($this->alteredIds[$account->id()][$group->id()])) {
      $ids = [];

      // Get the IDs from the 'group_roles' field, without loading the roles.
      if ($membership = $this->membershipLoader->load($group, $account)) {
        foreach ($membership->getGroupContent()->group_roles as $group_role_ref) {
          $ids[] = $group_role_ref->target_id;
        }
      }

      // Allow modules to alter the list of role IDs the user gets for the group.
      $this->moduleHandler->alter('group_user_roles', $ids, $account, $group);

      // Run some checks on what people actually tried to set here.
      foreach ($ids as $key => $id) {
        list($group_type_id, $role_id) = explode('-', $id . '-');

        // Filter out any roles that are not available to the group's type.
        if ($group_type_id != $group->bundle()) {
          unset($ids[$key]);
        }
        // Filter out roles that were malformed.
        elseif (empty($role_id)) {
          unset($ids[$key]);
        }
        // Filter out any special roles someone tried to manually set.
        elseif (in_array($role_id, ['anonymous', 'outsider', 'member'])) {
          unset($ids[$key]);
        }
      }

      // Only now do we add the implied group role IDs so there's no way someone
      // could have removed them in their alter hook.
      if ($include_implied) {
        if ($membership !== FALSE) {
          $ids[] = $group->getGroupType()->getMemberRoleId();
        }
        else {
          $ids[] = $account->isAnonymous()
            ? $group->getGroupType()->getAnonymousRoleId()
            : $group->getGroupType()->getOutsiderRoleId();
        }
      }

      $this->alteredIds[$account->id()][$group->id()] = $ids;
    }

    return $this->loadMultiple($this->alteredIds[$account->id()][$group->id()]);
  }

}
