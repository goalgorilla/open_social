<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\social_graphql\GraphQL\Violation;
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;

/**
 * The creation event input wrapper.
 *
 * Provides validation and easy access to the input to create an event.
 */
class CreateEventInput extends EventInputBase {

  use VisibilityTrait;

  /**
   * {@inheritdoc}
   */
  public function setValues(array $input): void {
    parent::setValues($input);
    assert(isset($input['body']) && $input['body'] instanceof ValidatedDocument, "GraphQL schema should ensure the body is a required rich text document.");

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

    // Validate event type if provided (optional for create).
    if (array_key_exists('type', $input)) {
      if ($input['type'] === '') {
        $this->violations[] = new Violation("EVENT_TYPE_INVALID");
      }
      elseif ($input['type'] !== NULL) {
        $event_types = $this->loadEventTypesByUuids([$input['type']]);
        if (isset($event_types[$input['type']])) {
          $this->eventType = $event_types[$input['type']];
        }
        else {
          $this->violations[] = new Violation("EVENT_TYPE_NOT_FOUND");
        }
      }
    }

    // Schema guarantees visibility: ContentVisibility!; validate enum value
    // only.
    if ($this->isValidVisibility($input['visibility'])) {
      $this->visibility = $input['visibility'];
    }
    else {
      $this->violations[] = new Violation("VISIBILITY_INVALID");
    }

    $renderer = new HtmlRenderer();
    $this->body = [
      'value' => $renderer->renderDocument($input['body']->getDocument()),
      'format' => $this->getBodyFieldTextFormat($this->actor),
    ];

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

    // Address is optional; validate and normalize when provided.
    if (isset($input['address']) && is_array($input['address'])) {
      $this->validateAndSetAddressFromInput($input['address']);
    }

    // Process groups if provided.
    if (array_key_exists('groups', $input) && $input['groups'] !== NULL && $this->actor !== NULL) {
      assert(is_string($this->visibility));
      $drupal_visibility = $this->convertVisibilityUserInputToConstant($this->visibility);
      $groups_result = $this->processGroups($input, $this->actor, $drupal_visibility, 'event', 'group_node:event');
      if ($groups_result !== NULL && $groups_result->isValid()) {
        $this->primaryGroup = $groups_result->getPrimaryGroup();
        $this->crosspostedGroups = $groups_result->getCrosspostedGroups();
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

}
