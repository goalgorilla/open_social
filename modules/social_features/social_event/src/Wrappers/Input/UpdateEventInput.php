<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\user\UserInterface;

/**
 * The update event input wrapper.
 *
 * Provides validation and easy access to the input to update an event.
 */
class UpdateEventInput extends EventInputBase {

  use VisibilityTrait;

  /**
   * The actor (current user performing the mutation).
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected ?AccountInterface $actor = NULL;

  /**
   * The author of the event.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $author = NULL;

  /**
   * The event node being updated.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $event = NULL;

  /**
   * Whether the location field was explicitly provided in the input.
   *
   * When true, location should be updated (to the string value or cleared if
   * null).
   */
  protected bool $locationProvided = FALSE;

  /**
   * The current user for the request.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Create a new Update Event Input instance.
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

    // Load actor from Account-Proxy.
    $this->actor = $this->currentUser->getAccount();

    // The author is loaded from user-data to ensure it exists.
    $author = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->actor->id());
    if (empty($author)) {
      $this->violations[] = new Violation("EVENT_NOT_FOUND");
      return;
    }
    $this->author = $author;

    // Load the event by UUID and bundle in a single query.
    $events = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'uuid' => $input['id'],
        'type' => 'event',
      ]);

    // Check if the event exists.
    $event = $events ? reset($events) : NULL;
    if (!$event instanceof NodeInterface) {
      $this->violations[] = new Violation("EVENT_NOT_FOUND");
      return;
    }

    // Check if the actor has permission to update this event.
    if (!$event->access('update', $this->actor)) {
      $this->violations[] = new Violation("EVENT_NOT_FOUND");
      return;
    }

    $this->event = $event;

    // Validate title if provided (optional for updates).
    if (isset($input['title'])) {
      if (!is_string($input['title'])) {
        $this->violations[] = new Violation("TITLE_INVALID");
      }
      else {
        $title = trim($input['title']);
        if (empty($title)) {
          $this->violations[] = new Violation("TITLE_REQUIRED");
        }
        elseif (mb_strlen($title) > 255) {
          $this->violations[] = new Violation("TITLE_TOO_LONG");
        }
        else {
          $this->title = $title;
        }
      }
    }

    // Validate event type if provided (optional for updates).
    if (isset($input['type'])) {
      if (empty($input['type'])) {
        $this->violations[] = new Violation("EVENT_TYPE_INVALID");
      }
      else {
        $event_types = $this->loadEventTypesByUuids([$input['type']]);
        if (isset($event_types[$input['type']])) {
          $this->eventType = $event_types[$input['type']];
        }
        else {
          $this->violations[] = new Violation("EVENT_TYPE_NOT_FOUND");
        }
      }
    }

    // Validate visibility if provided (optional for updates).
    if (isset($input['visibility'])) {
      if (!$this->isValidVisibility($input['visibility'])) {
        $this->violations[] = new Violation("VISIBILITY_INVALID");
      }
      else {
        $this->visibility = $input['visibility'];
      }
    }

    // Validate start date if provided (optional for updates).
    if (isset($input['startDate'])) {
      if (!is_int($input['startDate'])) {
        $this->violations[] = new Violation("START_DATE_INVALID");
      }
      else {
        $this->startDate = $input['startDate'];
      }
    }

    // Validate end date if provided (optional for updates).
    if (isset($input['endDate'])) {
      if (!is_int($input['endDate'])) {
        $this->violations[] = new Violation("END_DATE_INVALID");
      }
      else {
        $this->endDate = $input['endDate'];
      }
    }

    // Cross-field date validation against existing entity dates.
    $this->validateDateRange();

    // Handle location as optional string (same as CreateEventInput).
    // Omit = unchanged; null = clear; string = set (empty/whitespace = clear).
    if (array_key_exists('location', $input)) {
      $this->locationProvided = TRUE;
      if ($input['location'] === NULL || !is_string($input['location'])) {
        $this->location = NULL;
      }
      else {
        $trimmed = trim($input['location']);
        $this->location = $trimmed !== '' ? $trimmed : NULL;
      }
    }
  }

  /**
   * Validate date range using provided and/or existing entity dates.
   *
   * When only one date is provided, compares against the existing entity's
   * other date. When both are provided, compares them against each other.
   */
  protected function validateDateRange(): void {
    if ($this->event === NULL) {
      return;
    }

    $effective_start = $this->startDate;
    $effective_end = $this->endDate;

    // Fall back to existing entity dates for comparison.
    if ($effective_start === NULL) {
      $existing_start = $this->event->get('field_event_date')->value;
      if ($existing_start !== NULL) {
        $effective_start = (new \DateTimeImmutable($existing_start, new \DateTimeZone('UTC')))->getTimestamp();
      }
    }
    if ($effective_end === NULL) {
      $existing_end = $this->event->get('field_event_date_end')->value;
      if ($existing_end !== NULL) {
        $effective_end = (new \DateTimeImmutable($existing_end, new \DateTimeZone('UTC')))->getTimestamp();
      }
    }

    if ($effective_start !== NULL && $effective_end !== NULL && $effective_end < $effective_start) {
      $this->violations[] = new Violation("END_DATE_BEFORE_START_DATE");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): bool {
    // We can't validate if there were errors already.
    if ($this->hasViolations()) {
      return FALSE;
    }

    // Validate whether the author is anonymous, and it can't be NULL either.
    if ($this->author === NULL || $this->author->isAnonymous()) {
      $this->violations[] = new Violation("INVALID_USER");
      return FALSE;
    }

    // Validate event exists.
    if ($this->event === NULL) {
      $this->violations[] = new Violation("EVENT_NOT_FOUND");
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

  /**
   * Get the event node being updated.
   *
   * @return \Drupal\node\NodeInterface
   *   The event node.
   */
  public function getEvent(): NodeInterface {
    assert($this->event !== NULL, __FUNCTION__ . " called but event was not set.");
    return $this->event;
  }

  /**
   * Check if title should be updated.
   *
   * @return bool
   *   TRUE if title was provided.
   */
  public function hasTitle(): bool {
    return $this->title !== NULL;
  }

  /**
   * Check if event type should be updated.
   *
   * @return bool
   *   TRUE if event type was provided.
   */
  public function hasEventType(): bool {
    return $this->eventType !== NULL;
  }

  /**
   * Check if visibility should be updated.
   *
   * @return bool
   *   TRUE if visibility was provided.
   */
  public function hasVisibility(): bool {
    return $this->visibility !== NULL;
  }

  /**
   * Check if start date should be updated.
   *
   * @return bool
   *   TRUE if start date was provided.
   */
  public function hasStartDate(): bool {
    return $this->startDate !== NULL;
  }

  /**
   * Check if end date should be updated.
   *
   * @return bool
   *   TRUE if end date was provided.
   */
  public function hasEndDate(): bool {
    return $this->endDate !== NULL;
  }

  /**
   * Check if location should be updated.
   *
   * @return bool
   *   TRUE if the location field was explicitly provided in the input.
   */
  public function hasLocation(): bool {
    return $this->locationProvided;
  }

}
