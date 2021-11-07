<?php

namespace Drupal\ginvite;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Wrapper class for a GroupContent entity representing an invitation.
 *
 * Should be loaded through the 'group.invitation_loader' service.
 */
class GroupInvitation implements CacheableDependencyInterface {

  /**
   * The group content entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $groupContent;

  /**
   * Constructs a new GroupInvitation.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity representing the invitation.
   *
   * @throws \Exception
   *   Exception thrown when trying to instantiate this class with a
   *   GroupContent entity that was not based on the GroupInvitation content
   *   enabler plugin.
   */
  public function __construct(GroupContentInterface $group_content) {
    if ($group_content->getGroupContentType()->getContentPluginId() == 'group_invitation') {
      $this->groupContent = $group_content;
    }
    else {
      throw new \Exception('Trying to create a GroupInvitation from an incompatible GroupContent entity.');
    }
  }

  /**
   * Returns the fieldable GroupContent entity for the invitation.
   *
   *   The group content entity.
   */
  public function getGroupContent(): GroupContentInterface {
    return $this->groupContent;
  }

  /**
   * Returns the group for the invitation.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group entity where invite belongs.
   */
  public function getGroup(): GroupInterface {
    return $this->groupContent->getGroup();
  }

  /**
   * Returns the user for the invitation.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity referenced in invitation.
   */
  public function getUser(): EntityInterface {
    return $this->groupContent->getEntity();
  }

  /**
   * Returns the group roles for the invitation.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   An array of group roles, keyed by their ID.
   */
  public function getRoles(): array {
    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
    return $group_role_storage->loadByUserAndGroup($this->getUser(), $this->getGroup());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return $this->getGroupContent()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return $this->getGroupContent()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return $this->getGroupContent()->getCacheMaxAge();
  }

}
