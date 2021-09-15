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
   * The Social Profile field manager.
   *
   * @var \Drupal\social_profile\FieldManager
   */
  protected $fieldManager;

  /**
   * Create a new ProfileFieldsPermissionProvider instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Druapl entity type manager.
   * @param \Drupal\social_profile\FieldManager $fieldManager
   *   The Social Profile field manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FieldManager $fieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldManager = $fieldManager;
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

    // Create the permissions across bundles.
    // Gives access to SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE fields.
    $permissions["view any profile fields"] = [
      'title' => $this->t("View any profile fields"),
      'description' => $this->t("Allows a user to view all fields on a profile regardless of visibility settings. Prefer using a field specific permission instead as only those are reflected in the settings form."),
      'restrict access' => TRUE,
    ];

    // Gives access to SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY fields.
    $permissions["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " profile fields"] = [
      'title' => $this->t("View community profile fields"),
    ];

    foreach ($profile_types as $id => $profile_type) {
      // Create the permissions for all fields in a bundle.
      $permissions["view any ${$id} profile fields"] = [
        'title' => $this->t("View any %profile profile fields", ['%profile' => $profile_type->label()]),
        'description' => $this->t("Allows a user to view all fields on a profile regardless of visibility settings for the %bundle profile type. Prefer using a field specific permission instead as only those are reflected in the settings form.", ['%bundle' => $id]),
        'restrict access' => TRUE,
      ];

      $permissions["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_COMMUNITY . " ${$id} profile fields"] = [
        'title' => $this->t("View community %profile profile fields", ['%profile' => $profile_type->label()]),
      ];

      // Create the permissions per field per bundle.
      $fields = $this->fieldManager->getFieldDefinitions("profile", "profile");
      foreach ($fields as $field_name => $field_config) {
        if ($this->fieldManager::isOptedOutOfFieldAccessManagement($field_config)) {
          continue;
        }

        $permissions["view " . SOCIAL_PROFILE_FIELD_VISIBILITY_PRIVATE . " ${field_name} ${id} profile fields"] = [
          'title' => $this->t("View private %field %profile profile fields", ['%field' => $field_config->getLabel(), '%profile' => $profile_type->label()]),
          'description' => $this->t("Allows a user to view any %field field on a profile regardless of visibility settings for the %bundle profile type.", ['%field' => $field_name, '%bundle' => $id]),
        ];

        $permissions["edit own ${field_name} ${id} profile field"] = [
          'title' => $this->t("Edit own %field %profile profile field", ['%field' => $field_config->getLabel(), '%profile' => $profile_type->label()]),
        ];

        $permissions["edit any ${field_name} ${id} profile field"] = [
          'title' => $this->t("Edit any %field %profile profile field", ['%field' => $field_config->getLabel(), '%profile' => $profile_type->label()]),
        ];
      }
    }

    return $permissions;
  }

}
