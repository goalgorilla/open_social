<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\user\UserInterface;

/**
 * The creation event input wrapper.
 *
 * Provides validation and easy access to the input to create an event.
 */
class CreateEventInput extends EventInputBase {

  use VisibilityTrait;

  /**
   * The current user (actor).
   *
   * The actor is the user performing the mutation. This may differ from the
   * author in future iterations where events can be created on behalf of
   * another user.
   */
  private AccountProxyInterface $currentUser;

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
   * Create a new Create Event Input instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user for the request.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepositoryInterface $entity_repository,
    AccountProxyInterface $current_user,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $input): void {
    parent::setValues($input);

    $this->actor = $this->currentUser->getAccount();

    $author = $this->entityTypeManager->getStorage('user')->load($this->actor->id());
    if ($author === NULL) {
      $this->violations[] = new Violation("ACCESS_DENIED");
      return;
    }
    $this->author = $author;

    $node_access_handler = $this->entityTypeManager->getAccessControlHandler('node');
    if (!$node_access_handler->createAccess('event', $this->actor)) {
      $this->violations[] = new Violation("ACCESS_DENIED");
      return;
    }

    // Schema guarantees title: String!; reject empty after trim, validate
    // length.
    $this->title = trim($input['title']);
    if ($this->title === '') {
      $this->violations[] = new Violation("TITLE_REQUIRED");
      return;
    }
    if (mb_strlen($this->title) > 255) {
      $this->violations[] = new Violation("TITLE_TOO_LONG");
    }

    // Load event type by vocabulary; schema guarantees type is present (ID!).
    $event_types = $this->loadEventTypesByUuids([$input['type']]);
    if (isset($event_types[$input['type']])) {
      $this->eventType = $event_types[$input['type']];
    }
    else {
      $this->violations[] = new Violation("EVENT_TYPE_NOT_FOUND");
    }

    // Schema guarantees visibility: ContentVisibility!; validate enum value
    // only.
    if ($this->isValidVisibility($input['visibility'])) {
      $this->visibility = $input['visibility'];
    }
    else {
      $this->violations[] = new Violation("VISIBILITY_INVALID");
    }

    // Validate start date and end date as integer timestamps.
    // @todo Validate Timestamp (integer) in the GraphQL schema (e.g. Timestamp
    //   scalar parseValue/parseLiteral) so invalid values are rejected before
    //   reaching this input; then remove this validation.
    if (!is_int($input['startDate'])) {
      $this->violations[] = new Violation("START_DATE_REQUIRED");
    }
    else {
      $this->startDate = $input['startDate'];
    }
    if (!is_int($input['endDate'])) {
      $this->violations[] = new Violation("END_DATE_REQUIRED");
    }
    else {
      $this->endDate = $input['endDate'];
    }

    // Cross-field: end date must not be before start date.
    if ($this->startDate !== NULL && $this->endDate !== NULL && $this->endDate < $this->startDate) {
      $this->violations[] = new Violation("END_DATE_BEFORE_START_DATE");
    }

    // Location is optional; treat empty or whitespace-only as not provided.
    if (isset($input['location']) && is_string($input['location'])) {
      $trimmed = trim($input['location']);
      if ($trimmed !== '') {
        $this->location = $trimmed;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): bool {
    if ($this->hasViolations()) {
      return FALSE;
    }

    if ($this->author === NULL || $this->author->isAnonymous()) {
      $this->violations[] = new Violation("INVALID_USER");
      return FALSE;
    }

    return TRUE;
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

}
