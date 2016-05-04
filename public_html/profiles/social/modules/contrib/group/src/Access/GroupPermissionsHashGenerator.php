<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupPermissionsHashGenerator.
 */

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;

/**
 * Generates and caches the permissions hash for a group membership.
 */
class GroupPermissionsHashGenerator implements GroupPermissionsHashGeneratorInterface {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The cache backend interface to use for the persistent cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $static;

  /**
   * Constructs a GroupPermissionsHashGenerator object.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend interface to use for the persistent cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend interface to use for the static cache.
   */
  public function __construct(PrivateKey $private_key, CacheBackendInterface $cache, CacheBackendInterface $static) {
    $this->privateKey = $private_key;
    $this->cache = $cache;
    $this->static = $static;
  }

  /**
   * {@inheritdoc}
   *
   * Cached by role, invalidated whenever permissions change.
   */
  public function generate(GroupInterface $group, AccountInterface $account) {
    // If the user can bypass group access we return a unique hash.
    if ($account->hasPermission('bypass group access')) {
      return $this->hash('bypass-group-access');
    }

    // Retrieve all of the membership's roles.
    if ($group_membership = $group->getMember($account)) {
      $group_roles = $group_membership->getRoles();
    }
    // If the user isn't a member, retrieve the outsider or anonymous role.
    else {
      $role_name = $account->id() == 0
        ? $group->bundle() . '.outsider'
        : $group->bundle() . '.anonymous';
      $group_roles[$role_name] = GroupRole::load($role_name);
    }

    // Sort the group roles by ID.
    ksort($group_roles);

    // Create a cache ID based on the role IDs.
    $role_list = implode(',', array_keys($group_roles));
    $cid = "group_permissions_hash:$role_list";

    // Retrieve the hash from the static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    else {
      // Build cache tags for the individual group roles.
      $tags = Cache::buildTags('config:group.role', array_keys($group_roles), '.');

      // Retrieve the hash from the persistent cache if available.
      if ($cache = $this->cache->get($cid)) {
        $permissions_hash = $cache->data;
      }
      // Otherwise generate the hash and store it in the persistent cache.
      else {
        $permissions_hash = $this->doGenerate($group_roles);
        $this->cache->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
      }

      // Store the hash in the static cache.
      $this->static->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
    }

    return $permissions_hash;
  }

  /**
   * Generates a hash that uniquely identifies the group member's permissions.
   *
   * @param \Drupal\group\Entity\GroupRoleInterface[] $group_roles
   *   The group roles to generate the permission hash for.
   *
   * @return string
   *   The permissions hash.
   */
  protected function doGenerate(array $group_roles) {
    $permissions = [];
    foreach ($group_roles as $group_role) {
      $permissions = array_merge($permissions, $group_role->getPermissions());
    }
    return $this->hash(serialize(array_unique($permissions)));
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

}
