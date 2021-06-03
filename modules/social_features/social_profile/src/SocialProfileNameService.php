<?php

namespace Drupal\social_profile;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provide a service for Profile name.
 *
 * @package Drupal\social_profile
 */
class SocialProfileNameService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SocialProfileNameService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Whether or not need to update the Profile name.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return bool
   *   Whether or not need to update the Profile name.
   */
  public function needToUpdateProfileName(ProfileInterface $profile): bool {
    // Do nothing if no profile.
    if ($profile == NULL) {
      return FALSE;
    }

    $profile_name_fields = SocialProfileNameService::getProfileNameFields();

    /** @var \Drupal\Core\Entity\ContentEntityBase $original */
    $original = $profile->original ?? NULL;

    // If it is new Profile and we have no origin then we need set generated
    // Profile name field.
    if ($original == NULL) {
      return TRUE;
    }

    // If some of profile name fields changed we need update Profile name field.
    foreach ($profile_name_fields as $profile_name_field) {
      if (!$profile->get($profile_name_field)->equals($original->get($profile_name_field))) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the list of the fields which can contain in the Profile name.
   *
   * @return string[]
   *   List of the names of the fields.
   */
  public static function getProfileNameFields(): array {
    $fields = [
      'field_profile_first_name',
      'field_profile_last_name',
    ];
    if (\Drupal::moduleHandler()->moduleExists('social_profile_fields')) {
      $fields[] = 'field_profile_nick_name';
    }
    return $fields;
  }

  /**
   * Get profile name.
   *
   * @param \Drupal\profile\Entity\ProfileInterface|null $profile
   *   The profile.
   *
   * @return string|void
   *   The generated profile name value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProfileName(ProfileInterface $profile = NULL) {
    // Do nothing if no profile.
    if ($profile == NULL) {
      return '';
    }

    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');

    /** @var \Drupal\user\UserInterface $account */
    $account = $user_storage->load($profile->getOwnerId());

    // Set default profile name.
    $account_name = $account->getAccountName();

    // If enable module social_profile_privacy we need get hidden fields and
    // later check if the Profile name fields are there.
    $private_fields_list = [];
    if ($this->moduleHandler->moduleExists('social_profile_privacy')) {
      // Get profile private fields list.
      $uid = $account->id();
      $private_fields_list = social_profile_privacy_private_fields_list($uid);
    }

    $account_name_fields = SocialProfileNameService::getProfileNameFields();

    // We do nothing further if all fields of the Profile name are hidden.
    if (count(array_intersect($private_fields_list, $account_name_fields)) == count($account_name_fields)) {
      return $account_name;
    }

    $account_name_values = [];

    // We need set Nickname as Profile name if that field is not hidden and not
    // empty.
    if (!in_array('field_profile_nick_name', $private_fields_list) && in_array('field_profile_nick_name', $account_name_fields) && !empty($nick_name = $profile->get('field_profile_nick_name')->getString())) {
      $account_name = $nick_name;
    }
    else {
      // We need concatenate Firstname and Lastname for Profile name if at least
      // one of those fields are not hidden and not empty.
      foreach ($account_name_fields as $account_name_field) {
        if (!in_array($account_name_field, $private_fields_list) && !empty($name_field = $profile->get($account_name_field)->getString())) {
          $account_name_values[] = $name_field;
        }
      }
      if (!empty($account_name_values) && !empty($full_name = implode(" ", $account_name_values))) {
        $account_name = $full_name;
      }
    }

    return $account_name;
  }

}
