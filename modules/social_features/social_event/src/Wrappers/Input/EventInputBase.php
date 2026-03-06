<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Exception\UnknownCountryException;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_graphql\Exception\ShouldNotHappenException;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\InputBase;
use Drupal\social_group_flexible_group\Service\GroupInputValidationService;
use Drupal\social_group_flexible_group\ValueObject\GroupInputValidationResult;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base class for event input wrappers.
 *
 * Provides shared validation logic and common fields for creating and updating
 * events.
 */
abstract class EventInputBase extends InputBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The current user for the request.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The country repository for address validation.
   */
  protected CountryRepositoryInterface $countryRepository;

  /**
   * The actor (current user performing the mutation).
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected ?AccountInterface $actor = NULL;

  /**
   * The author of the event.
   *
   * The author is the user who will be credited as the creator of the event.
   * In the initial iteration, the author is the same as the actor. However,
   * access checks should always be performed against the author to ensure
   * that only users with permission to create events can be set as authors.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $author = NULL;

  /**
   * The body field.
   *
   * Contains the HTML (from Rich Text JSON conversion) and text format.
   *
   * @var array{value: string, format: string}|null
   */
  protected ?array $body = NULL;

  /**
   * The title of the event.
   */
  protected ?string $title = NULL;

  /**
   * The event type.
   */
  protected ?TermInterface $eventType = NULL;

  /**
   * The visibility setting.
   */
  protected ?string $visibility = NULL;

  /**
   * The start date as a unix timestamp.
   */
  protected ?int $startDate = NULL;

  /**
   * The end date as a unix timestamp.
   */
  protected ?int $endDate = NULL;

  /**
   * The location of the event.
   */
  protected ?string $location = NULL;

  /**
   * The address of the event in Drupal storage shape (snake_case keys).
   *
   * @var array<string, string>|null
   */
  protected ?array $address = NULL;

  /**
   * The group input validation service.
   *
   * @var \Drupal\social_group_flexible_group\Service\GroupInputValidationService|null
   */
  protected ?GroupInputValidationService $groupInputValidationService = NULL;

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
   * Constructs an EventInputBase instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user for the request.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository for validating address country codes.
   * @param \Drupal\social_group_flexible_group\Service\GroupInputValidationService|null $group_input_validation_service
   *   The group input validation service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepositoryInterface $entity_repository,
    AccountProxyInterface $current_user,
    CountryRepositoryInterface $country_repository,
    ?GroupInputValidationService $group_input_validation_service = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->currentUser = $current_user;
    $this->countryRepository = $country_repository;
    $this->groupInputValidationService = $group_input_validation_service;
  }

  /**
   * Get the actor (current user performing the mutation).
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The actor.
   */
  public function getActor(): AccountInterface {
    assert($this->actor !== NULL, __FUNCTION__ . " called but actor was not set.");
    return $this->actor;
  }

  /**
   * Get the author.
   *
   * @return \Drupal\user\UserInterface
   *   The author.
   */
  public function getAuthor(): UserInterface {
    assert($this->author !== NULL, __FUNCTION__ . " called but author was not set.");
    return $this->author;
  }

  /**
   * Get the body field values.
   *
   * @return array{value: string, format: string}
   *   The body.
   */
  public function getBody(): array {
    assert($this->body !== NULL, __FUNCTION__ . " called but body was not set.");
    return $this->body;
  }

  /**
   * Get the text format that should be used in the body field.
   *
   * Until we decide that all content is created with a specific text format
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
      throw new ShouldNotHappenException("The application that is trying to create an event does not have access to any usable text formats. It's expected that the scopes that allow access to content creation also provide access to at least one text format to be used.");
    }
    $format_id = reset($allowed_formats)->id();
    assert(is_string($format_id), "Expected TextFormats to be saved config with string IDs.");

    return $format_id;
  }

  /**
   * Load event type taxonomy terms by their UUIDs.
   *
   * Loading by vocabulary guarantees terms are from the event_types bundle,
   * so callers need not check bundle() or instanceof.
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
  protected function loadEventTypesByUuids(array $uuids): array {
    if (empty($uuids)) {
      return [];
    }
    /** @var \Drupal\taxonomy\TermInterface[] $terms_by_id */
    $terms_by_id = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'uuid' => $uuids,
        'vid' => 'event_types',
      ]);
    $terms = [];
    foreach ($terms_by_id as $term) {
      $terms[$term->uuid()] = $term;
    }
    return $terms;
  }

  /**
   * Get the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle(): string {
    assert($this->title !== NULL, __FUNCTION__ . " called but title was not set.");
    return $this->title;
  }

  /**
   * Check if event type is set.
   *
   * @return bool
   *   TRUE if event type was provided and is valid.
   */
  public function hasEventType(): bool {
    return $this->eventType !== NULL;
  }

  /**
   * Get the event type.
   *
   * Returns the event type term. In the base implementation, an assert ensures
   * callers have verified hasEventType() before calling. Subclasses may
   * override to return NULL (e.g. UpdateEventInput for clearing).
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The event type, or NULL.
   */
  public function getEventType(): ?TermInterface {
    assert($this->eventType !== NULL, __FUNCTION__ . " called but event type was not set.");
    return $this->eventType;
  }

  /**
   * Get the visibility.
   *
   * @return string
   *   The visibility setting.
   */
  public function getVisibility(): string {
    assert($this->visibility !== NULL, __FUNCTION__ . " called but visibility was not set.");
    return $this->visibility;
  }

  /**
   * Get the start date as a unix timestamp.
   *
   * @return int
   *   The start date timestamp.
   */
  public function getStartDate(): int {
    assert($this->startDate !== NULL, __FUNCTION__ . " called but start date was not set.");
    return $this->startDate;
  }

  /**
   * Get the end date as a unix timestamp.
   *
   * @return int
   *   The end date timestamp.
   */
  public function getEndDate(): int {
    assert($this->endDate !== NULL, __FUNCTION__ . " called but end date was not set.");
    return $this->endDate;
  }

  /**
   * Get the location.
   *
   * @return string|null
   *   The location or NULL if not provided.
   */
  public function getLocation(): ?string {
    return $this->location;
  }

  /**
   * Get the address in Drupal storage shape (snake_case keys).
   *
   * @return array<string, string>|null
   *   The address or NULL if not provided.
   */
  public function getAddress(): ?array {
    return $this->address;
  }

  /**
   * Validates address input and sets $this->address when valid.
   *
   * Adds ADDRESS_COUNTRY_CODE_REQUIRED or ADDRESS_COUNTRY_CODE_INVALID to
   * violations when invalid. Does not set $this->address when validation fails.
   *
   * @param array $address_input
   *   Raw address input.
   */
  protected function validateAndSetAddressFromInput(array $address_input): void {
    $country_code = strtoupper(trim($address_input['countryCode'] ?? ''));
    if ($country_code === '') {
      $this->violations[] = new Violation("ADDRESS_COUNTRY_CODE_REQUIRED");
      return;
    }
    try {
      $this->countryRepository->get($country_code);
      $this->address = [
        'country_code' => $country_code,
        'administrative_area' => trim($address_input['administrativeArea'] ?? ''),
        'locality' => trim($address_input['locality'] ?? ''),
        'dependent_locality' => trim($address_input['dependentLocality'] ?? ''),
        'postal_code' => trim($address_input['postalCode'] ?? ''),
        'address_line1' => trim($address_input['addressLine1'] ?? ''),
        'address_line2' => trim($address_input['addressLine2'] ?? ''),
      ];
    }
    catch (UnknownCountryException $e) {
      $this->violations[] = new Violation("ADDRESS_COUNTRY_CODE_INVALID");
    }
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
   * @param string $bundle
   *   The entity bundle (e.g. 'event').
   * @param string $plugin_id
   *   The group content plugin ID (e.g. 'group_node:event').
   *
   * @return \Drupal\social_group_flexible_group\ValueObject\GroupInputValidationResult|null
   *   The validation result, or NULL if groups were not provided.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processGroups(array $input, AccountInterface $actor, ?string $visibility, string $bundle = 'event', string $plugin_id = 'group_node:event'): ?GroupInputValidationResult {
    if (!array_key_exists('groups', $input) || $input['groups'] === NULL) {
      return NULL;
    }

    if ($this->groupInputValidationService === NULL) {
      $this->violations[] = new Violation('GROUPS_NOT_SUPPORTED');
      return NULL;
    }

    $validation_result = $this->groupInputValidationService->validateGroupsForContent(
      $input['groups'],
      $bundle,
      $visibility,
      $actor,
      $plugin_id
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
    return $this->primaryGroup;
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

}
