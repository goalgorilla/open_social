<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_graphql\Exception\ShouldNotHappenException;
use Drupal\social_graphql\Wrappers\InputBase;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

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
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepositoryInterface $entity_repository,
    AccountProxyInterface $current_user,
    CountryRepositoryInterface $country_repository,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->currentUser = $current_user;
    $this->countryRepository = $country_repository;
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
   * Normalizes GraphQL address input to Drupal address field value shape.
   *
   * @param array $input
   *   The raw address input (camelCase keys from GraphQL).
   *
   * @return array{country_code: string, administrative_area: string, locality: string, dependent_locality: string, postal_code: string, address_line1: string, address_line2: string}
   *   Address value with Drupal keys (snake_case). Langcode is left empty;
   *   the mutation adds it when building the node value.
   */
  protected function normalizeAddressInputToDrupal(array $input): array {
    $value = [
      'country_code' => trim($input['countryCode'] ?? ''),
      'administrative_area' => trim($input['administrativeArea'] ?? ''),
      'locality' => trim($input['locality'] ?? ''),
      'dependent_locality' => trim($input['dependentLocality'] ?? ''),
      'postal_code' => trim($input['postalCode'] ?? ''),
      'address_line1' => trim($input['addressLine1'] ?? ''),
      'address_line2' => trim($input['addressLine2'] ?? ''),
    ];
    return $value;
  }

}
