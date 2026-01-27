<?php

declare(strict_types=1);

namespace Drupal\social_topic\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\InputBase;

/**
 * The delete topic input wrapper.
 *
 * Provides validation and easy access to the input to delete a topic.
 */
class DeleteTopicInput extends InputBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The current user (actor).
   *
   * The actor is the user performing the mutation.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The topic node to delete.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $topic = NULL;

  /**
   * Create a new Delete Topic Input instance.
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

    // Validate topic ID.
    if (empty($input['id'])) {
      $this->violations[] = new Violation("TOPIC_ID_REQUIRED");
      return;
    }

    // Load the topic by UUID and bundle in a single query.
    $topics = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'uuid' => $input['id'],
        'type' => 'topic',
      ]);

    // Check if the topic exists.
    $topic = $topics ? reset($topics) : NULL;
    if (!$topic instanceof NodeInterface) {
      $this->violations[] = new Violation("TOPIC_NOT_FOUND");
      return;
    }

    // Check if the user has permission to delete this topic.
    if (!$topic->access('delete', $actor)) {
      $this->violations[] = new Violation("ACCESS_DENIED");
      return;
    }

    $this->topic = $topic;
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
   * Get the topic to delete.
   *
   * @return \Drupal\node\NodeInterface
   *   The topic node.
   */
  public function getTopic(): NodeInterface {
    assert($this->topic !== NULL, __FUNCTION__ . " called but topic was not set.");
    return $this->topic;
  }

}
