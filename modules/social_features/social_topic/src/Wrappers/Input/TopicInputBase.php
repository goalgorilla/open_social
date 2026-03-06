<?php

declare(strict_types=1);

namespace Drupal\social_topic\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\InputBase;
use Drupal\social_group_flexible_group\Service\GroupInputValidationService;
use Drupal\social_group_flexible_group\ValueObject\GroupInputValidationResult;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\social_graphql\Exception\ShouldNotHappenException;
use Drupal\social_organization\Service\OrganizationInputValidationService;
use Drupal\social_organization\ValueObject\OrganizationInputValidationResult;

/**
 * Base class for topic input wrappers.
 *
 * Provides shared validation logic for creating and updating topics.
 */
abstract class TopicInputBase extends InputBase {

  /**
   * Maximum number of content tags allowed per topic.
   *
   * This limit prevents Denial of Service attacks where an attacker could
   * send a large number of tag UUIDs to overload the database.
   */
  const MAX_CONTENT_TAGS = 50;

  /**
   * The node bundle for topic content.
   *
   * Used when validating organization input for topic-in-group relationships.
   */
  const CONTENT_BUNDLE = 'topic';

  /**
   * Validated primary group data.
   *
   * @var \Drupal\group\Entity\GroupInterface|null
   */
  protected ?GroupInterface $primaryGroup = NULL;

  /**
   * Validated crossposted group data.
   *
   * @var \Drupal\group\Entity\GroupInterface[]|null
   */
  protected ?array $crosspostedGroups = NULL;

  /**
   * Validated primary organization data.
   *
   * @var \Drupal\group\Entity\GroupInterface|null
   */
  protected ?GroupInterface $primaryOrganization = NULL;

  /**
   * Validated crossposted organization data.
   *
   * @var \Drupal\group\Entity\GroupInterface[]
   */
  protected array $crosspostedOrganizations = [];

  /**
   * Constructs a TopicInputBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\social_group_flexible_group\Service\GroupInputValidationService|null $groupInputValidationService
   *   The group input validation service.
   * @param \Drupal\social_organization\Service\OrganizationInputValidationService|null $organizationInputValidationService
   *   The organization input validation service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
    protected ?GroupInputValidationService $groupInputValidationService = NULL,
    protected ?OrganizationInputValidationService $organizationInputValidationService = NULL,
  ) {
  }

  /**
   * Get the text format that should be used in the body field.
   *
   * Until we decide that all topics are created with a specific text format
   * and that this is not dependent on users' permission we must figure out
   * what the default text format is that the user can use and use that.
   * Get a list of formats for this user, ordered by weight. The first one
   * available is the user's default format.
   *
   * @param \Drupal\Core\Session\AccountInterface $actor
   *   The actor that's updating the content. The format depends on what they
   *   have access to.
   *
   * @return string
   *   The format ID.
   */
  protected function getBodyFieldTextFormat(AccountInterface $actor) : string {
    $allowed_formats = \filter_formats($actor);
    if ($allowed_formats === []) {
      throw new ShouldNotHappenException("The application that is trying to create a topic does not have access to any usable text formats. It's expected that the scopes that allow access to content creation also provide access to at least one text format to be used.");
    }
    $format_id = reset($allowed_formats)->id();
    assert(is_string($format_id), "Expected TextFormats to be saved config with string IDs.");

    return $format_id;
  }

  /**
   * Validate and load content tags.
   *
   * This method performs all necessary validation for content tags:
   * - Validates input format (must be an array)
   * - Checks maximum number of tags limit
   * - Checks if the tag exists and is of the correct vocabulary
   * - Checks if the tag is published
   * - Checks if the tag can be used for node_topic.
   *   If the tag doesn't have field_category_usage configured, it inherits
   *   the usage from its parent term (following the hierarchy pattern used
   *   in the social_tagging module).
   *
   * @param mixed $content_tags_input
   *   The content tags input (should be an array of tag UUIDs).
   *
   * @return array
   *   An associative array with two keys:
   *   - 'valid_tags': Array of validated TermInterface objects.
   *   - 'violations': Array of Violation objects for any validation errors.
   */
  protected function validateContentTags($content_tags_input): array {
    $valid_tags = [];
    $violations = [];

    // Validate input format - must be an array.
    if (!is_array($content_tags_input)) {
      $violations[] = new Violation("CONTENT_TAG_INVALID_INPUT");
      return [
        'valid_tags' => $valid_tags,
        'violations' => $violations,
      ];
    }

    // Check maximum number of tags limit to prevent DoS attacks.
    if (count($content_tags_input) > self::MAX_CONTENT_TAGS) {
      $violations[] = new Violation("CONTENT_TAGS_LIMIT_EXCEEDED");
      return [
        'valid_tags' => $valid_tags,
        'violations' => $violations,
      ];
    }

    // Empty array is valid (no tags to assign).
    if (empty($content_tags_input)) {
      return [
        'valid_tags' => $valid_tags,
        'violations' => $violations,
      ];
    }

    // Load all tags in a single query to avoid N+1 problem.
    $tags_by_uuid = $this->loadTermsByUuids($content_tags_input);

    foreach ($content_tags_input as $tag_uuid) {
      // Check if tag exists and is from the correct vocabulary.
      if (!isset($tags_by_uuid[$tag_uuid])) {
        $violations[] = new Violation("CONTENT_TAG_NOT_FOUND:" . $tag_uuid);
        continue;
      }

      $tag = $tags_by_uuid[$tag_uuid];

      // Check if tag can be used for node_topic.
      // First, check if the tag itself has field_category_usage.
      $has_field = $tag->hasField('field_category_usage') && !$tag->get('field_category_usage')->isEmpty();
      $usage = $has_field ? unserialize($tag->get('field_category_usage')->value, ['allowed_classes' => FALSE]) : NULL;

      $is_valid = FALSE;

      if ($has_field && is_array($usage)) {
        // Tag has explicit configuration - check if it includes node_topic.
        if (in_array('node_topic', $usage, TRUE)) {
          $is_valid = TRUE;
        }
        // If tag has explicit configuration but doesn't include node_topic,
        // don't check parent (explicit configuration takes precedence).
      }
      else {
        // Tag doesn't have field_category_usage - check parent for inheritance.
        $parent_ids = [];
        foreach ($tag->get('parent') as $parent_item) {
          $parent_value = $parent_item->getValue();
          $parent_id = $parent_value['target_id'] ?? NULL;
          if ($parent_id !== NULL && $parent_id > 0) {
            $parent_ids[] = $parent_id;
          }
        }

        if (!empty($parent_ids)) {
          // Load the first parent (taxonomy terms typically have one parent).
          $parent = $this->entityTypeManager
            ->getStorage('taxonomy_term')
            ->load(reset($parent_ids));

          if ($parent && $parent->hasField('field_category_usage') && !$parent->get('field_category_usage')->isEmpty()) {
            $parent_usage = unserialize($parent->get('field_category_usage')->value, ['allowed_classes' => FALSE]);
            if (is_array($parent_usage) && in_array('node_topic', $parent_usage, TRUE)) {
              $is_valid = TRUE;
            }
          }
        }
      }

      // If tag is not valid (neither itself nor parent has valid usage),
      // reject it.
      if (!$is_valid) {
        $violations[] = new Violation("CONTENT_TAG_INVALID_USAGE:" . $tag_uuid);
        continue;
      }

      // Tag is valid - add to the list.
      $valid_tags[] = $tag;
    }

    return [
      'valid_tags' => $valid_tags,
      'violations' => $violations,
    ];
  }

  /**
   * Process content tags from input.
   *
   * @param array $input
   *   The input array that may contain 'contentTags'.
   *
   * @return array|null
   *   An array with two keys: 'valid_tags' and 'violations'.
   *   Returns NULL if 'contentTags' key is not present or if it is null.
   */
  protected function processContentTags(array $input): ?array {
    // Process content tags if provided.
    if (!array_key_exists('contentTags', $input)) {
      return NULL;
    }

    // Treat null as "not provided".
    if ($input['contentTags'] === NULL) {
      return NULL;
    }

    $validation_result = $this->validateContentTags($input['contentTags']);

    // Add all violations found during validation.
    if (!empty($validation_result['violations'])) {
      $this->violations = array_merge($this->violations, $validation_result['violations']);
    }

    return $validation_result;
  }

  /**
   * Load taxonomy terms by their UUIDs in a single query.
   *
   * @param array $uuids
   *   The UUIDs of the terms to load.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of terms indexed by their UUIDs. Returns an empty array
   *   if no matching entities are found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo Remove when https://www.drupal.org/project/drupal/issues/3214923 lands.
   */
  protected function loadTermsByUuids(array $uuids): array {
    /** @var \Drupal\taxonomy\TermInterface[] $terms_by_id */
    $terms_by_id = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'uuid' => $uuids,
        'vid' => 'social_tagging',
        'status' => 1,
      ]);
    $terms = [];
    foreach ($terms_by_id as $term) {
      $terms[$term->uuid()] = $term;
    }

    return $terms;
  }

  /**
   * Process groups input and validate all group-related rules.
   *
   * @param array $input
   *   The input array.
   * @param \Drupal\Core\Session\AccountInterface $actor
   *   The actor account.
   * @param string|null $visibility
   *   The visibility value.
   *
   * @return \Drupal\social_group_flexible_group\ValueObject\GroupInputValidationResult|null
   *   The validation result, or NULL if groups were not provided.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processGroups(array $input, AccountInterface $actor, ?string $visibility = NULL): ?GroupInputValidationResult {
    if (!array_key_exists('groups', $input) || $input['groups'] === NULL) {
      return NULL;
    }

    if ($this->groupInputValidationService === NULL) {
      $this->violations[] = new Violation('GROUPS_NOT_SUPPORTED');
      return NULL;
    }

    $validation_result = $this->groupInputValidationService->validateGroupsForContent(
      $input['groups'],
      'topic',
      $visibility,
      $actor,
      'group_node:topic'
    );

    // Convert error strings to Violation objects.
    if (!$validation_result->isValid()) {
      $this->violations = array_merge(
        $this->violations,
        array_map(fn($error_code) => new Violation((string) $error_code), $validation_result->getErrors())
      );
    }

    return $validation_result;
  }

  /**
   * Converts constraint violations to GraphQL violations.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The constraint violations.
   *
   * @return \Drupal\social_graphql\GraphQL\Violation[]
   *   Array of GraphQL violation objects.
   */
  public function convertConstraintViolations(ConstraintViolationListInterface $violations): array {
    $graphql_violations = [];

    foreach ($violations as $violation) {
      // Create a violation ID based on the constraint type and field.
      $property_path = $violation->getPropertyPath();
      $constraint = $violation->getConstraint();

      // Skip if constraint is null (Make PHPStan happy).
      if ($constraint === NULL) {
        continue;
      }

      $constraint_class = get_class($constraint);
      $last_separator_pos = strrpos($constraint_class, '\\');
      $constraint_type = $last_separator_pos !== FALSE
        ? substr($constraint_class, $last_separator_pos + 1)
        : $constraint_class;

      // Create a machine-readable violation ID.
      $violation_id = strtoupper($property_path . '_' . $constraint_type);
      $violation_id = preg_replace('/[^A-Z0-9_]/', '_', $violation_id);

      // Add violation if ID is valid.
      if (is_string($violation_id)) {
        $graphql_violations[] = new Violation($violation_id);
      }
    }

    return $graphql_violations;
  }

  /**
   * Get primary group.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The primary group or NULL.
   */
  public function getPrimaryGroup(): ?GroupInterface {
    return $this->primaryGroup ?? NULL;
  }

  /**
   * Get cross-posted groups.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   Array of cross-posted groups.
   */
  public function getCrosspostedGroups(): array {
    return $this->crosspostedGroups ?? [];
  }

  /**
   * Process organizations input and validate all organization-related rules.
   *
   * @param array $input
   *   The input array.
   * @param \Drupal\Core\Session\AccountInterface $actor
   *   The actor account.
   *
   * @return \Drupal\social_organization\ValueObject\OrganizationInputValidationResult|null
   *   The validation result, or NULL if organizations were not provided.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processOrganizations(array $input, AccountInterface $actor): ?OrganizationInputValidationResult {
    if (!array_key_exists('organizations', $input) || $input['organizations'] === NULL) {
      return NULL;
    }

    if ($this->organizationInputValidationService === NULL) {
      $this->violations[] = new Violation('ORGANIZATIONS_NOT_SUPPORTED');
      return NULL;
    }

    // Validate organizations for topic.
    $validation_result = $this->organizationInputValidationService->validateOrganizationsForContent(
      $input['organizations'],
      self::CONTENT_BUNDLE,
      $actor,
    );

    // Convert error strings to Violation objects.
    if (!$validation_result->isValid()) {
      $this->violations = array_merge(
        $this->violations,
        array_map(fn($error_code) => new Violation((string) $error_code), $validation_result->getErrors())
      );
    }

    return $validation_result;
  }

  /**
   * Get primary organization.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The primary organization or NULL.
   */
  public function getPrimaryOrganization(): ?GroupInterface {
    return $this->primaryOrganization;
  }

  /**
   * Get cross-posted organizations.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   Array of cross-posted organizations.
   */
  public function getCrosspostedOrganizations(): array {
    return $this->crosspostedOrganizations;
  }

}
