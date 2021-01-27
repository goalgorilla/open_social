<?php

namespace Drupal\social_profile_fields;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialProfileFieldsHelper.
 */
class SocialProfileFieldsHelper {

  /**
   * Entity type manager for loading entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new SocialProfileFieldsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager for loading entities.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Functions fetches profile fields from a profile type.
   *
   * @param string $profile_type_id
   *   The profile bundle.
   *
   * @return array
   *   An array of fields.
   */
  public function getProfileFields($profile_type_id) {
    $fields = [];

    // Use storage to get only the profile fields of the current bundle type.
    try {
      $profile_fields = $this->entityTypeManager->getStorage('field_config')->loadByProperties(['entity_type' => 'profile', 'bundle' => $profile_type_id]);
    }
    catch (\Exception $e) {
      return $fields;
    }

    // Loop through the fields and return the necessary values.
    /** @var \Drupal\Core\Field\FieldConfigInterface $profile_field */
    foreach ($profile_fields as $profile_field) {
      // Rewrite the ID a bit, since otherwise config thinks it's an array.
      $id = str_replace('.', '_', $profile_field->id());
      // Build the array.
      $fields[$id] = [
        'id' => $id,
        'name' => $profile_field->getName(),
        'label' => $profile_field->getLabel(),
      ];
    }

    // Return the array of fields.
    return $fields;
  }

  /**
   * Get the user export plugin ids for a given field.
   *
   * @param string $field_id
   *   The field id, e.g. profile_profile_field_profile_address.
   *
   * @return array
   *   An array of plugins.
   */
  public function getUserExportPluginIdForField($field_id) {
    $mapping = $this->mapProfileFieldsToUserExportPlugin();
    return array_keys($mapping, $field_id);
  }

  /**
   * Map profile fields to user export plugins.
   *
   * @return array
   *   An array of fields and user export plugins.
   */
  public function mapProfileFieldsToUserExportPlugin() {
    $mapping = [
      'user_first_name' => 'profile_profile_field_profile_first_name',
      'user_last_name' => 'profile_profile_field_profile_last_name',
      'user_address_country_code' => 'profile_profile_field_profile_address',
      'user_address_administrative' => 'profile_profile_field_profile_address',
      'user_address_locality' => 'profile_address_field_city',
      'user_address_postal_code' => 'profile_address_field_postalcode',
      'user_address_line1' => 'profile_address_field_address',
      'user_address_line2' => 'profile_profile_field_profile_address',
      'user_phone_number' => 'profile_profile_field_profile_phone_number',
      'user_organization' => 'profile_profile_field_profile_organization',
      'user_function' => 'profile_profile_field_profile_function',
      'user_skills' => 'profile_profile_field_profile_expertise',
      'user_interests' => 'profile_profile_field_profile_interests',
      'user_profile_tag' => 'profile_profile_field_profile_profile_tag',
      'user_nickname' => 'profile_profile_field_profile_nick_name',
    ];

    // Allow other modules to alter the mapping.
    $this->moduleHandler->alter('profile_field_export_mapping', $mapping);

    return $mapping;
  }

}
