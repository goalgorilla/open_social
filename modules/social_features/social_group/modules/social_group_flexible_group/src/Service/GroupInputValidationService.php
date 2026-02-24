<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\social_group_flexible_group\ValueObject\GroupInputValidationResult;

/**
 * Service for validating group input in API.
 */
class GroupInputValidationService {

  /**
   * Maximum number of cross-posted groups allowed per content.
   */
  private const MAX_CROSSPOSTED_GROUPS = 50;

  /**
   * Constructs a GroupInputValidationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Validates that primary group is provided in input.
   *
   * @param array $groups_input
   *   The groups input array.
   *
   * @return string[]|null
   *   Array with error code if validation fails, null otherwise.
   */
  private function validatePrimaryGroupRequired(array $groups_input): ?array {
    if (empty($groups_input['group'])) {
      return ["PRIMARY_GROUP_REQUIRED"];
    }
    return NULL;
  }

  /**
   * Loads a group by UUID.
   *
   * @param string $uuid
   *   The group UUID.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The loaded group entity or null if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadGroup(string $uuid): ?GroupInterface {
    $group_storage = $this->entityTypeManager->getStorage('group');
    $groups = $group_storage->loadByProperties([
      'uuid' => $uuid,
      'type' => 'flexible_group',
    ]);
    $group_entity = $groups ? reset($groups) : NULL;
    return $group_entity instanceof GroupInterface ? $group_entity : NULL;
  }

  /**
   * Checks if cross-posted groups exceed the maximum limit.
   *
   * @param array $crossposted_uuids
   *   Array of cross-posted group UUIDs.
   *
   * @return string[]|null
   *   Array with error code if limit exceeded, null otherwise.
   */
  private function checkLimitExceededCrossposted(array $crossposted_uuids): ?array {
    if (count($crossposted_uuids) > self::MAX_CROSSPOSTED_GROUPS) {
      return ["LIMIT_EXCEEDED_FOR_CROSSPOSTED_GROUPS"];
    }
    return NULL;
  }

  /**
   * Checks for duplicate groups in cross-posted list.
   *
   * @param string $primary_group_uuid
   *   The primary group UUID.
   * @param array $crossposted_uuids
   *   Array of cross-posted group UUIDs.
   *
   * @return string[]|null
   *   Array with error code if duplicates found, null otherwise.
   */
  private function checkDuplicateGroup(string $primary_group_uuid, array $crossposted_uuids): ?array {
    if (
      in_array($primary_group_uuid, $crossposted_uuids, TRUE)
      || count($crossposted_uuids) !== count(array_unique($crossposted_uuids))
    ) {
      return ["GROUP_DUPLICATE"];
    }
    return NULL;
  }

  /**
   * Loads cross-posted groups by UUIDs.
   *
   * @param array $crossposted_uuids
   *   Array of cross-posted group UUIDs.
   *
   * @return array{groups: \Drupal\group\Entity\GroupInterface[], errors: string[]}
   *   Array with loaded groups and any errors encountered.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadCrosspostedGroups(array $crossposted_uuids): array {
    $crossposted_groups = [];
    $errors = [];

    foreach ($crossposted_uuids as $uuid) {
      $group_entity = $this->loadGroup($uuid);
      if ($group_entity === NULL) {
        $errors[] = "CROSSPOSTED_GROUP_NOT_FOUND:" . $uuid;
        continue;
      }

      $crossposted_groups[] = $group_entity;
    }

    return ['groups' => $crossposted_groups, 'errors' => $errors];
  }

  /**
   * Validates cross-posting rules.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   Array of groups to validate.
   * @param string $bundle
   *   The content bundle.
   *
   * @return string[]
   *   Array of error code strings.
   */
  protected function isCrossPostingEnabled(array $groups, string $bundle): array {
    $errors = [];
    $group_settings = $this->configFactory->get('social_group.settings');

    // Check if cross-posting is enabled globally.
    if (!$group_settings->get('cross_posting.status')) {
      $errors[] = "CROSS_POSTING_IS_DISABLED";
      return $errors;
    }

    // Check if content type is allowed for cross-posting.
    $allowed_content_types = $group_settings->get('cross_posting.content_types');
    if (!in_array($bundle, $allowed_content_types ?? [], TRUE)) {
      $errors[] = "CROSS_POSTING_IS_DISABLED";
      return $errors;
    }

    // Check if all group types are allowed for cross-posting.
    $allowed_group_types = $group_settings->get('cross_posting.group_types');
    foreach ($groups as $group) {
      $group_type_id = $group->getGroupType()->id();
      if (!in_array($group_type_id, $allowed_group_types ?? [], TRUE)) {
        $errors[] = "CROSS_POSTING_IS_DISABLED";
        return $errors;
      }
    }

    return $errors;
  }

  /**
   * Validates visibility against groups allowed visibility options.
   *
   * @param string $visibility
   *   The visibility value.
   * @param \Drupal\group\Entity\GroupInterface $primary_group
   *   The primary group.
   * @param array $crossposted_groups
   *   Array of cross-posted groups.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return string[]
   *   Array of error code strings.
   */
  private function validateGroupVisibility(string $visibility, GroupInterface $primary_group, array $crossposted_groups, AccountInterface $account): array {
    $errors = [];

    $all_groups = array_merge([$primary_group], $crossposted_groups);
    $allowed_options = \social_group_get_allowed_content_visibility_options_for_multiple_groups(
      $all_groups,
      NULL,
      $account
    );

    // Check if the selected visibility is allowed.
    if (!isset($allowed_options[$visibility]) || !$allowed_options[$visibility]) {
      $errors[] = "VISIBILITY_NOT_ALLOWED_IN_GROUP";
    }

    return $errors;
  }

  /**
   * Validates permissions for groups using access handlers.
   *
   * @param \Drupal\group\Entity\GroupInterface $primary_group
   *   The primary group.
   * @param \Drupal\group\Entity\GroupInterface[] $crossposted_groups
   *   Array of cross-posted groups.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param string $plugin_id
   *   The group content plugin-id.
   *
   * @return string[]
   *   Array of error code strings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function validateGroupPermissions(
    GroupInterface $primary_group,
    array $crossposted_groups,
    AccountInterface $account,
    string $plugin_id,
  ): array {
    $errors = [];

    $group_content_storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($group_content_storage instanceof GroupRelationshipTypeStorageInterface);
    $group_content_access_handler = $this->entityTypeManager->getAccessControlHandler('group_content');

    // Validate permission for primary group.
    $relationship_type_id = $group_content_storage->getRelationshipTypeId(
      $primary_group->bundle(),
      $plugin_id
    );
    if (!$group_content_access_handler->createAccess($relationship_type_id, $account, ['group' => $primary_group])) {
      $errors[] = "GROUP_NOT_FOUND";
      return $errors;
    }

    // Validate permission for each cross-posted group.
    foreach ($crossposted_groups as $group) {
      $relationship_type_id = $group_content_storage->getRelationshipTypeId(
        $group->bundle(),
        $plugin_id
      );
      if (!$group_content_access_handler->createAccess($relationship_type_id, $account, ['group' => $group])) {
        $errors[] = "GROUP_NOT_FOUND";
        return $errors;
      }
    }

    return $errors;
  }

  /**
   * Validates groups input for content creation/update.
   *
   * @param array $groups_input
   *   The groups input array.
   * @param string $bundle
   *   The entity bundle.
   * @param string|null $visibility
   *   The visibility value.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check permissions and visibility.
   * @param string $plugin_id
   *   The group content plugin-id.
   *
   * @return \Drupal\social_group_flexible_group\ValueObject\GroupInputValidationResult
   *   The validation result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function validateGroupsForContent(
    array $groups_input,
    string $bundle,
    ?string $visibility,
    AccountInterface $account,
    string $plugin_id,
  ): GroupInputValidationResult {
    $errors = [];

    // Validate primary group is provided.
    $required_error = $this->validatePrimaryGroupRequired($groups_input);
    if ($required_error !== NULL) {
      return new GroupInputValidationResult($required_error, NULL, []);
    }

    // Load primary group.
    $primary_group = $this->loadGroup($groups_input['group']);
    if ($primary_group === NULL) {
      return new GroupInputValidationResult(["GROUP_NOT_FOUND"], NULL, []);
    }

    $crossposted_groups = [];

    // Process cross-posted groups if provided.
    if (!empty($groups_input['crosspostedGroups'])) {
      $crossposted_uuids = $groups_input['crosspostedGroups'];

      // Check maximum limit.
      $limit_error = $this->checkLimitExceededCrossposted($crossposted_uuids);
      if ($limit_error !== NULL) {
        return new GroupInputValidationResult($limit_error, $primary_group, []);
      }

      // Check for duplicates.
      $duplicate_error = $this->checkDuplicateGroup($groups_input['group'], $crossposted_uuids);
      if ($duplicate_error !== NULL) {
        return new GroupInputValidationResult($duplicate_error, $primary_group, []);
      }

      // Load cross-posted groups.
      $load_result = $this->loadCrosspostedGroups($crossposted_uuids);
      $crossposted_groups = $load_result['groups'];
      $errors = array_merge($errors, $load_result['errors']);

      // Validate cross-posting rules if we have multiple groups.
      if (count($crossposted_groups) > 0) {
        $all_groups = array_merge([$primary_group], $crossposted_groups);
        if (count($all_groups) > 1) {
          $cross_posting_errors = $this->isCrossPostingEnabled($all_groups, $bundle);
          $errors = array_merge($errors, $cross_posting_errors);
        }
      }
    }

    $permission_errors = $this->validateGroupPermissions(
      $primary_group,
      $crossposted_groups,
      $account,
      $plugin_id
    );
    $errors = array_merge($errors, $permission_errors);
    if (!empty($errors)) {
      return new GroupInputValidationResult($errors, $primary_group, $crossposted_groups);
    }

    if ($visibility !== NULL) {
      $visibility_errors = $this->validateGroupVisibility(
        $visibility,
        $primary_group,
        $crossposted_groups,
        $account
      );
      $errors = array_merge($errors, $visibility_errors);
    }

    return new GroupInputValidationResult($errors, $primary_group, $crossposted_groups);
  }

}
