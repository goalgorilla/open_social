<?php

declare(strict_types=1);

namespace Drupal\social_topic\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * The creation topic input wrapper.
 *
 * Provides validation and easy access to the input to create a topic.
 */
class CreateTopicInput extends TopicInputBase {
  use VisibilityTrait;

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
   * The actor is the user performing the mutation. This may differ from the
   * author in future iterations where topics can be created on behalf of
   * another user.
   */
  private AccountProxyInterface $currentUser;

  /**
   * The actor (current user performing the mutation).
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected ?AccountInterface $actor;

  /**
   * The author of the topic.
   *
   * The author is the user who will be credited as the creator of the topic.
   * In the initial iteration, the author is the same as the actor. However,
   * access checks should always be performed against the author to ensure
   * that only users with permission to create topics can be set as authors.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $author;

  /**
   * The title of the topic.
   */
  protected ?string $title = NULL;

  /**
   * The topic type.
   */
  protected ?TermInterface $topicType = NULL;

  /**
   * The visibility setting.
   */
  protected ?string $visibility = NULL;

  /**
   * Content tags for the topic.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected array $contentTags = [];

  /**
   * Create a new Create Topic Input instance.
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

    // In the initial iteration, the author is the same as the actor.
    // In future iterations, the author may be specified in the input.
    // The author is loaded from user-data to ensure it exists.
    $author = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->actor->id());
    if ($author === NULL) {
      $this->violations[] = new Violation("ACCESS_DENIED");
      return;
    }
    $this->author = $author;

    // Check if the actor has permission to create topics.
    $node_access_handler = $this->entityTypeManager->getAccessControlHandler('node');
    if (!$node_access_handler->createAccess('topic', $this->actor)) {
      $this->violations[] = new Violation("ACCESS_DENIED");
      return;
    }

    // Validate title.
    if (empty($input['title']) || !is_string($input['title'])) {
      $this->violations[] = new Violation("TITLE_REQUIRED");
      return;
    }
    $this->title = trim($input['title']);

    // Validate title length (max 255 characters for node title).
    if (mb_strlen($this->title) > 255) {
      $this->violations[] = new Violation("TITLE_TOO_LONG");
    }

    // Validate topic type.
    if (empty($input['type'])) {
      $this->violations[] = new Violation("TOPIC_TYPE_REQUIRED");
    }
    else {
      /** @var \Drupal\taxonomy\TermInterface|null $topic_type */
      $topic_type = $this->entityRepository->loadEntityByUuid('taxonomy_term', $input['type']);
      if (!$topic_type instanceof TermInterface || $topic_type->bundle() !== 'topic_types') {
        $this->violations[] = new Violation("TOPIC_TYPE_NOT_FOUND");
      }
      else {
        $this->topicType = $topic_type;
      }
    }

    // Validate visibility.
    if (empty($input['visibility'])) {
      $this->violations[] = new Violation("VISIBILITY_REQUIRED");
    }
    else {
      if (!$this->isValidVisibility($input['visibility'])) {
        $this->violations[] = new Violation("VISIBILITY_INVALID");
      }
      else {
        $this->visibility = $input['visibility'];
      }
    }

    // Process content tags if provided.
    $content_tags_result = $this->processContentTags($input);
    if ($content_tags_result !== NULL && empty($content_tags_result['violations'])) {
      $this->contentTags = $content_tags_result['valid_tags'];
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

    // Validate author (should never be null at this point due to constructor).
    if ($this->author->isAnonymous()) {
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
   * The author is the user who will be credited as the creator of the topic.
   * Access checks should always be performed against the author.
   *
   * @return \Drupal\user\UserInterface
   *   The author.
   */
  public function getAuthor(): UserInterface {
    return $this->author;
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
   * Get the topic type.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The topic type.
   */
  public function getTopicType(): TermInterface {
    assert($this->topicType !== NULL, __FUNCTION__ . " called but topic type was not set.");
    return $this->topicType;
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
   * Get content tags.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Array of content tags.
   */
  public function getContentTags(): array {
    return $this->contentTags;
  }

}
