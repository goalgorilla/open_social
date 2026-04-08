<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\FileInput;
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
   * Whether the groups field was explicitly provided in the input.
   *
   * When true, groups should be updated (set, or remove if value null).
   */
  protected bool $groupsProvided = FALSE;

  /**
   * Whether the organizations field was explicitly provided in the input.
   *
   * When true, organizations should be updated (set, or remove if null).
   */
  protected bool $organizationsProvided = FALSE;

  /**
   * The content tags.
   *
   * NULL when contentTags was not provided in input (unchanged).
   * Set to term array when contentTags was provided and valid (including []).
   *
   * @var \Drupal\taxonomy\TermInterface[]|null
   */
  protected ?array $contentTags = NULL;

  /**
   * Optional hero image for the event.
   *
   * @var \Drupal\social_graphql\Wrappers\FileInput|null
   */
  protected ?FileInput $heroImage = NULL;

  /**
   * Whether the heroImage key was present in the GraphQL input.
   *
   * @var bool
   */
  protected bool $heroImageProvided = FALSE;

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

    // Process hero image if the key was sent (omit = unchanged; null = clear).
    if (array_key_exists('heroImage', $input)) {
      $this->heroImageProvided = TRUE;
      $this->heroImage = $input['heroImage'] !== NULL ? FileInput::fromGraphQlInput($input['heroImage']) : NULL;
    }

    // Process content tags if provided (optional for updates).
    // Omit = unchanged; null = clear tags (processContentTags returns valid
    // empty result); [] or [uuid, ...] = validate and set (or clear when []).
    if (array_key_exists('contentTags', $input)) {
      $content_tags_result = $this->processContentTags($input);
      if ($content_tags_result !== NULL && $content_tags_result->isValid()) {
        $this->contentTags = $content_tags_result->getTags();
      }
    }

    // --- Groups (optional on update) ---
    // Semantics: omit = leave groups unchanged; null = remove from all groups;
    // object with "group" key = set primary/crossposted groups.
    if (array_key_exists('groups', $input)) {
      $this->groupsProvided = TRUE;
      if ($input['groups'] === NULL) {
        // Explicit null: remove event from all groups.
        $this->primaryGroup = NULL;
        $this->crosspostedGroups = [];
      }
      elseif (!is_array($input['groups']) || !array_key_exists('group', $input['groups'])) {
        // Wrong shape (missing or invalid "group" key): record violation, do
        // not apply.
        $this->violations[] = new Violation("GROUPS_INVALID");
        $this->groupsProvided = FALSE;
      }
      else {
        // Valid shape: resolve groups and apply if actor is set and result is
        // valid.
        $groups_input = ['groups' => $input['groups']];
        $event_visibility = $this->getEventVisibilityForGroups();
        if ($this->actor !== NULL) {
          $groups_result = $this->processGroups($groups_input, $this->actor, $event_visibility, 'event', 'group_node:event');
          if ($groups_result !== NULL && $groups_result->isValid()) {
            $this->primaryGroup = $groups_result->getPrimaryGroup();
            $this->crosspostedGroups = $groups_result->getCrosspostedGroups();
          }
        }
      }
    }

    // --- Organizations (optional on update) ---
    // Semantics: omit = leave organizations unchanged; null = clear all;
    // object with "organization" key = set primary/crossposted organizations.
    if (array_key_exists('organizations', $input)) {
      $this->organizationsProvided = TRUE;
      if ($input['organizations'] === NULL) {
        // Explicit null: clear all organizations.
        $this->primaryOrganization = NULL;
        $this->crosspostedOrganizations = [];
      }
      elseif (!is_array($input['organizations']) || !array_key_exists('organization', $input['organizations'])) {
        // Wrong shape (missing or invalid "organization" key): record
        // violation, do not apply.
        $this->violations[] = new Violation("ORGANIZATIONS_INVALID");
        $this->organizationsProvided = FALSE;
      }
      elseif ($this->actor !== NULL) {
        // Valid shape: resolve organizations and apply if result is valid.
        $organizations_result = $this->processOrganizations(
          ['organizations' => $input['organizations']],
          $this->actor
        );
        if ($organizations_result !== NULL && $organizations_result->isValid()) {
          $this->primaryOrganization = $organizations_result->getPrimaryOrganization();
          $this->crosspostedOrganizations = $organizations_result->getCrosspostedOrganizations();
        }
        else {
          // Validation or resolution failed: do not treat as provided.
          $this->organizationsProvided = FALSE;
        }
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

    if (
      $this->getEventVisibilityForGroups() === 'group'
      && !$this->hasEffectiveGroups()
      && !$this->hasEffectiveOrganizations()
    ) {
      $this->violations[] = new Violation("GROUP_REQUIRED_FOR_GROUP_VISIBILITY");
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Whether the event will have groups after this mutation.
   *
   * Uses the input groups if provided, otherwise the existing entity state.
   */
  private function hasEffectiveGroups(): bool {
    assert($this->event !== NULL);

    if ($this->groupsProvided) {
      return $this->primaryGroup !== NULL || !empty($this->crosspostedGroups);
    }

    return $this->event->hasField('groups')
      && !$this->event->get('groups')->isEmpty();
  }

  /**
   * Whether the event will have organizations after this mutation.
   *
   * Uses the input organizations if provided, otherwise the existing entity
   * state.
   */
  private function hasEffectiveOrganizations(): bool {
    assert($this->event !== NULL);

    if ($this->organizationsProvided) {
      return $this->primaryOrganization !== NULL
        || !empty($this->crosspostedOrganizations);
    }

    return $this->event->hasField(self::ORGANIZATIONS_GROUP_FIELD)
      && !$this->event->get(self::ORGANIZATIONS_GROUP_FIELD)->isEmpty();
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
   * Gets the image field input.
   *
   * @return \Drupal\social_graphql\Wrappers\FileInput|null
   *   The FileInput for the image, or NULL if not set.
   */
  public function getHeroImage(): ?FileInput {
    return $this->heroImage;
  }

  /**
   * Whether the hero image field was present in the mutation input.
   *
   * @return bool
   *   TRUE when the client sent heroImage (including null to clear).
   */
  public function hasHeroImageUpdate(): bool {
    return $this->heroImageProvided;
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

  /**
   * Check if groups should be updated.
   *
   * @return bool
   *   TRUE if the groups field was explicitly provided in the input.
   */
  public function hasGroups(): bool {
    return $this->groupsProvided;
  }

  /**
   * Check if content tags should be updated.
   *
   * @return bool
   *   TRUE if the contentTags field was provided in the input and validated.
   */
  public function hasContentTags(): bool {
    return $this->contentTags !== NULL;
  }

  /**
   * Get the content tags.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The content tags (empty array when cleared).
   */
  public function getContentTags(): array {
    assert($this->contentTags !== NULL, __FUNCTION__ . " called but content tags were not set.");
    return $this->contentTags;
  }

  /**
   * Check if organizations should be updated.
   *
   * @return bool
   *   TRUE if the organizations field was explicitly provided in the input.
   */
  public function hasOrganizationsUpdate(): bool {
    return $this->organizationsProvided;
  }

  /**
   * Check if organizations should be cleared (removed from all).
   *
   * When value was null we never set primaryOrganization from input, so it
   * stays NULL while organizationsProvided is TRUE.
   *
   * @return bool
   *   TRUE if organizations value was explicitly null.
   */
  public function shouldClearOrganizations(): bool {
    return $this->organizationsProvided && $this->primaryOrganization === NULL;
  }

  /**
   * Gets the visibility value to use when processing groups.
   *
   * Uses input visibility if provided, otherwise the event's current
   * visibility.
   *
   * @return string|null
   *   The Drupal visibility constant, or NULL if none available.
   */
  private function getEventVisibilityForGroups(): ?string {
    if ($this->visibility !== NULL) {
      return $this->convertVisibilityUserInputToConstant($this->visibility);
    }
    if ($this->event !== NULL && $this->event->hasField('field_content_visibility') && !$this->event->get('field_content_visibility')->isEmpty()) {
      return $this->event->get('field_content_visibility')->value;
    }
    return NULL;
  }

}
