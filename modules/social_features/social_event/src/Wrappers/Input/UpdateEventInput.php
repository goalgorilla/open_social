<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\taxonomy\TermInterface;
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;

/**
 * The update event input wrapper.
 *
 * Provides validation and easy access to the input to update an event.
 */
class UpdateEventInput extends EventInputBase {

  use VisibilityTrait;

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
   * Whether the type (event type) field was explicitly provided in the input.
   *
   * When true, event type should be updated (set to the term or cleared if
   * null).
   */
  protected bool $eventTypeProvided = FALSE;

  /**
   * Whether the address field was explicitly provided in the input.
   *
   * When true, address should be updated (to the value or cleared if null).
   */
  protected bool $addressProvided = FALSE;

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

    // Handle event type: omit = unchanged; null = clear; value = set.
    if (array_key_exists('type', $input)) {
      $this->eventTypeProvided = TRUE;
      if ($input['type'] === NULL) {
        $this->eventType = NULL;
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

    // Handle address: omit = unchanged; null = clear; value = set.
    if (array_key_exists('address', $input)) {
      $this->addressProvided = TRUE;
      if ($input['address'] === NULL) {
        $this->address = NULL;
      }
      elseif (!is_array($input['address'])) {
        $this->violations[] = new Violation("ADDRESS_INVALID");
      }
      else {
        $this->validateAndSetAddressFromInput($input['address']);
      }
    }

    // Process body if provided (optional for updates).
    if (isset($input['body'])) {
      assert($input['body'] instanceof ValidatedDocument, "GraphQL schema should ensure body is a ValidatedDocument when present.");
      assert($this->actor !== NULL, "Actor must be set before processing body.");

      $renderer = new HtmlRenderer();
      $this->body = [
        'value' => $renderer->renderDocument($input['body']->getDocument()),
        'format' => $this->getBodyFieldTextFormat($this->actor),
      ];
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
   * Check whether the event type field was explicitly provided in the input.
   *
   * When true, the resolver should update the field (set to term or clear).
   *
   * @return bool
   *   TRUE if type was in the input (set or null to clear).
   */
  public function eventTypeProvided(): bool {
    return $this->eventTypeProvided;
  }

  /**
   * Get the event type, or NULL if type was provided as null (clear).
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The event type term, or NULL to clear the field.
   */
  public function getEventType(): ?TermInterface {
    return $this->eventType;
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
   * Check if visibility should be updated.
   *
   * @return bool
   *   TRUE if visibility was provided.
   */
  public function hasVisibility(): bool {
    return $this->visibility !== NULL;
  }

  /**
   * Check if body should be updated.
   *
   * @return bool
   *   TRUE if body should be updated.
   */
  public function hasBody(): bool {
    return $this->body !== NULL;
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

  /**
   * Check if address should be updated.
   *
   * @return bool
   *   TRUE if the address field was explicitly provided in the input.
   */
  public function hasAddress(): bool {
    return $this->addressProvided;
  }

}
