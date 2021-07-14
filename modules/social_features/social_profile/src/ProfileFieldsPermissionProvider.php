<?php

namespace Drupal\social_profile;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides access control permissions for profile field visibility.
 *
 * This class is turned into service and used as permission callback in
 * social_profile.permissions.yml.
 */
class ProfileFieldsPermissionProvider {

  use StringTranslationTrait;

  /**
   * The Drupal entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create a new ProfileFieldsPermissionProvider instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Druapl entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gather the permissions that can be used to control profile field access.
   *
   * @return array
   *   The generated permissions.
   */
  public function permissions() : array {
    /** @var \Drupal\profile\Entity\ProfileType[] $profile_types */
    $profile_types = $this->entityTypeManager->getStorage('profile_type')->loadMultiple();

    // Users without permissions have SOCIAL_PROFILE_FIELD_VISIBILITY_PUBLIC
    // access only.
    $permissions = [];

    // Gives access to SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE fields.
    $permissions["view any profile fields"] = [];

    // Gives access to SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY fields.
    $permissions["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile fields"] = [];

    foreach ($profile_types as $id => $profile_type) {
      $permissions["view any ${$id} profile fields"] = [];

      $permissions["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " ${$id} profile fields"] = [];
    }

    // @todo We could add per-field permissions here if we want more control for
    // certain roles.
    return $permissions;
  }

}
