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
use Drupal\social_tagging\Service\ContentTagInputValidationServiceInterface;
use Drupal\social_tagging\ValueObject\ContentTagInputValidationResult;

/**
 * Base class for topic input wrappers.
 *
 * Provides shared validation logic for creating and updating topics.
 */
abstract class TopicInputBase extends InputBase {

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
   * @param \Drupal\social_tagging\Service\ContentTagInputValidationServiceInterface|null $contentTagInputValidationService
   *   The content tag input validation service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
    protected ?GroupInputValidationService $groupInputValidationService = NULL,
    protected ?OrganizationInputValidationService $organizationInputValidationService = NULL,
    protected ?ContentTagInputValidationServiceInterface $contentTagInputValidationService = NULL,
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
   * Process content tags from input.
   *
   * @param array $input
   *   The input array that may contain 'contentTags'.
   *
   * @return \Drupal\social_tagging\ValueObject\ContentTagInputValidationResult|null
   *   The validation result, or NULL when the 'contentTags' key is absent
   *   (omitted). When the key is present, behavior depends on validation
   *   service availability: if the service is available, a null value yields
   *   a valid result with empty tags (explicit "clear tags" intent) and
   *   non-null values are validated and their result returned; if the
   *   service is unavailable, CONTENT_TAGS_NOT_SUPPORTED is added and NULL
   *   is returned for either a null or non-null value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processContentTags(array $input): ?ContentTagInputValidationResult {
    if (!array_key_exists('contentTags', $input)) {
      return NULL;
    }

    // Explicit null: caller intends to clear tags. Return valid empty result
    // only when the validation service is available.
    if ($input['contentTags'] === NULL) {
      if ($this->contentTagInputValidationService === NULL) {
        $this->violations[] = new Violation('CONTENT_TAGS_NOT_SUPPORTED');
        return NULL;
      }
      return new ContentTagInputValidationResult([], []);
    }

    if ($this->contentTagInputValidationService === NULL) {
      $this->violations[] = new Violation('CONTENT_TAGS_NOT_SUPPORTED');
      return NULL;
    }

    $validation_result = $this->contentTagInputValidationService->validateContentTagsForContent(
      $input['contentTags'],
      'node_' . self::CONTENT_BUNDLE,
    );

    if (!$validation_result->isValid()) {
      $this->violations = array_merge(
        $this->violations,
        array_map(fn(string $error_code) => new Violation($error_code), $validation_result->getErrors())
      );
    }

    return $validation_result;
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
   * Whether the input currently has a primary flexible group assigned.
   */
  protected function hasAssignedPrimaryGroups(): bool {
    return $this->primaryGroup !== NULL;
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
