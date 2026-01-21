<?php

declare(strict_types=1);

namespace Drupal\social_topic\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\node\NodeInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\InputBase;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * The update topic input wrapper.
 *
 * Provides validation and easy access to the input to update a topic.
 */
class UpdateTopicInput extends InputBase {

  use VisibilityTrait;

  /**
   * The actor (current user performing the mutation).
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected ?AccountInterface $actor = NULL;

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
   * The topic node being updated.
   *
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $topic = NULL;

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
   * Create a new Update Topic Input instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user for the request.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
    protected AccountProxyInterface $currentUser,
  ) {}

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
      $this->violations[] = new Violation("TOPIC_NOT_FOUND");
      return;
    }
    $this->author = $author;

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

    // Check if the actor has permission to update this topic.
    if (!$topic->access('update', $this->actor)) {
      $this->violations[] = new Violation("TOPIC_NOT_FOUND");
      return;
    }

    $this->topic = $topic;

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

    // Validate topic type if provided (optional for updates).
    if (isset($input['type'])) {
      if (empty($input['type'])) {
        $this->violations[] = new Violation("TOPIC_TYPE_INVALID");
      }
      else {
        // Load the topic by UUID and bundle in a single query.
        $terms = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'uuid' => $input['type'],
            'vid' => 'topic_types',
          ]);

        // Check if the topic exists.
        $topic_type = $terms ? reset($terms) : NULL;
        if (!$topic_type instanceof TermInterface) {
          $this->violations[] = new Violation("TOPIC_TYPE_NOT_FOUND");
          return;
        }
        else {
          $this->topicType = $topic_type;
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
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): bool {
    // We can't validate if there were errors already.
    if ($this->hasViolations()) {
      return FALSE;
    }

    // Validate author is anonymous, it can't be NULL.
    if ($this->author->isAnonymous()) {
      $this->violations[] = new Violation("INVALID_USER");
      return FALSE;
    }

    // Validate topic exists.
    if ($this->topic === NULL) {
      $this->violations[] = new Violation("TOPIC_NOT_FOUND");
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
   * Get the topic node.
   *
   * @return \Drupal\node\NodeInterface
   *   The topic node.
   */
  public function getTopic(): NodeInterface {
    assert($this->topic !== NULL, __FUNCTION__ . " called but topic was not set.");
    return $this->topic;
  }

  /**
   * Check if title should be updated.
   *
   * @return bool
   *   TRUE if title should be updated.
   */
  public function hasTitle(): bool {
    return $this->title !== NULL;
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
   * Check if topic type should be updated.
   *
   * @return bool
   *   TRUE if topic type should be updated.
   */
  public function hasTopicType(): bool {
    return $this->topicType !== NULL;
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
   * Check if visibility should be updated.
   *
   * @return bool
   *   TRUE if visibility should be updated.
   */
  public function hasVisibility(): bool {
    return $this->visibility !== NULL;
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

}
