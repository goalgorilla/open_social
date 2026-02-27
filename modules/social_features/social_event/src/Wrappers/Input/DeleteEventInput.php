<?php

declare(strict_types=1);

namespace Drupal\social_event\Wrappers\Input;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\InputBase;

/**
 * The delete event input wrapper.
 *
 * Provides validation and easy access to the input to delete an event.
 */
class DeleteEventInput extends InputBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user (actor).
   *
   * The actor is the user performing the mutation.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The event node to delete.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $event = NULL;

  /**
   * Create a new Delete Event Input instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user for the request.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setValues(array $input): void {
    parent::setValues($input);

    // Load the current user from OAuth Session.
    $actor = $this->currentUser->getAccount();

    // Validate that the current user is authenticated.
    if ($actor->isAnonymous()) {
      $this->violations[] = new Violation("ACCESS_DENIED");
      return;
    }

    // Validate event ID.
    if (empty($input['id'])) {
      $this->violations[] = new Violation("EVENT_ID_REQUIRED");
      return;
    }

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

    // Check if the user has permission to delete this event.
    if (!$event->access('delete', $actor)) {
      $this->violations[] = new Violation("EVENT_NOT_FOUND");
      return;
    }

    $this->event = $event;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): bool {
    // We can't validate if there were errors already.
    if ($this->hasViolations()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get the event to delete.
   *
   * @return \Drupal\node\NodeInterface
   *   The event node.
   */
  public function getEvent(): NodeInterface {
    assert($this->event !== NULL, __FUNCTION__ . " called but event was not set.");
    return $this->event;
  }

}
