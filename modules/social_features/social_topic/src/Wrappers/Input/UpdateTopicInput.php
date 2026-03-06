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
use Drupal\social_group_flexible_group\Service\GroupInputValidationService;
use Drupal\social_organization\Service\OrganizationInputValidationService;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;

/**
 * The update topic input wrapper.
 *
 * Provides validation and easy access to the input to update a topic.
 */
class UpdateTopicInput extends TopicInputBase {

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
   * The body field.
   *
   * Contains the HTML (from Rich Text JSON conversion) and text format.
   * Only set when body was provided in the input.
   *
   * @var array{value: string, format: string}|null
   */
  protected ?array $body = NULL;

  /**
   * The content tags.
   *
   * @var \Drupal\taxonomy\TermInterface[]|null
   */
  protected ?array $contentTags = NULL;

  /**
   * Flag to track if groups field was provided in input.
   *
   * @var bool
   */
  protected bool $groupsProvided = FALSE;

  /**
   * Flag to track if organizations field was provided in input.
   *
   * @var bool
   */
  protected bool $organizationsProvided = FALSE;

  /**
   * Create a new Update Topic Input instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user for the request.
   * @param \Drupal\social_group_flexible_group\Service\GroupInputValidationService|null $groupInputValidationService
   *   The group input validation service.
   * @param \Drupal\social_organization\Service\OrganizationInputValidationService|null $organizationInputValidationService
   *   The organization input validation service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    protected AccountProxyInterface $currentUser,
    ?GroupInputValidationService $groupInputValidationService = NULL,
    ?OrganizationInputValidationService $organizationInputValidationService = NULL,
  ) {
    parent::__construct(
      $entityTypeManager,
      $entityRepository,
      $groupInputValidationService,
      $organizationInputValidationService,
    );
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

    // Process body if provided (optional for updates).
    if (isset($input['body'])) {
      assert($input['body'] instanceof ValidatedDocument, "GraphQL schema should ensure body is a ValidatedDocument when present.");

      $renderer = new HtmlRenderer();
      $this->body = [
        'value' => $renderer->renderDocument($input['body']->getDocument()),
        'format' => $this->getBodyFieldTextFormat($this->actor),
      ];
    }

    // Process content tags if provided.
    $content_tags_result = $this->processContentTags($input);
    if ($content_tags_result !== NULL && empty($content_tags_result['violations'])) {
      $this->contentTags = $content_tags_result['valid_tags'];
    }

    // Process groups if provided.
    if (array_key_exists('groups', $input)) {
      $this->groupsProvided = TRUE;

      if ($input['groups'] === NULL) {
        // groups: NULL means remove from all groups.
        $this->primaryGroup = NULL;
        $this->crosspostedGroups = [];
      }
      elseif (!is_array($input['groups'])) {
        $this->groupsProvided = FALSE;
        $this->violations[] = new Violation("GROUPS_INVALID");
      }
      else {
        // groups: ContentInGroupInput means update groups.
        $topic_visibility = $this->getTopicVisibilityForGroups();
        $groups_result = $this->processGroups($input, $this->actor, $topic_visibility);
        if ($groups_result !== NULL && $groups_result->isValid()) {
          $this->primaryGroup = $groups_result->getPrimaryGroup();
          $this->crosspostedGroups = $groups_result->getCrosspostedGroups();
        }
      }
    }

    // Process organizations if provided (UpdateContentInOrganizationInput).
    if (array_key_exists('organizations', $input)) {
      $this->organizationsProvided = TRUE;

      if ($input['organizations'] === NULL) {
        // organizations: NULL means don't change organizations.
        $this->organizationsProvided = FALSE;
      }
      elseif (!is_array($input['organizations']) || !array_key_exists('value', $input['organizations'])) {
        $this->organizationsProvided = FALSE;
        $this->violations[] = new Violation("ORGANIZATIONS_INVALID");
      }
      elseif ($input['organizations']['value'] === NULL) {
        // organizations: { value: null } means remove from all.
        $this->primaryOrganization = NULL;
        $this->crosspostedOrganizations = [];
      }
      else {
        // organizations: { value: ContentInOrganizationInput } means update.
        $organizations_result = $this->processOrganizations(
          ['organizations' => $input['organizations']['value']],
          $this->actor
        );

        if ($organizations_result !== NULL && $organizations_result->isValid()) {
          $this->primaryOrganization = $organizations_result->getPrimaryOrganization();
          $this->crosspostedOrganizations = $organizations_result->getCrosspostedOrganizations();
        }
        else {
          $this->organizationsProvided = FALSE;
        }
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
   * Check if body should be updated.
   *
   * @return bool
   *   TRUE if body should be updated.
   */
  public function hasBody(): bool {
    return $this->body !== NULL;
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
   * Return if content tags has tags.
   *
   * @return bool
   *   TRUE if content tags is not empty.
   */
  public function hasContentTags(): bool {
    return $this->contentTags !== NULL;
  }

  /**
   * Get the content tags.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The content tags.
   */
  public function getContentTags(): array {
    assert($this->contentTags !== NULL, __FUNCTION__ . " called but content tags were not set.");
    return $this->contentTags;
  }

  /**
   * Check if groups should be updated.
   *
   * @return bool
   *   TRUE if groups should be updated or removed.
   */
  public function hasGroups(): bool {
    return $this->groupsProvided;
  }

  /**
   * Gets the topic visibility value for group processing.
   *
   * Uses the visibility from input if provided, otherwise falls back to
   * the existing topic's visibility field value.
   *
   * @return string|null
   *   The visibility constant value or NULL if not available.
   */
  private function getTopicVisibilityForGroups(): ?string {
    if ($this->visibility !== NULL) {
      return $this->convertVisibilityUserInputToConstant($this->visibility);
    }

    if ($this->topic !== NULL && $this->topic->hasField('field_content_visibility') && !$this->topic->get('field_content_visibility')->isEmpty()) {
      return $this->topic->get('field_content_visibility')->value;
    }

    return NULL;
  }

  /**
   * Check if organizations should be updated.
   *
   * @return bool
   *   TRUE if organizations input was provided (clear or set).
   */
  public function hasOrganizationsUpdate(): bool {
    return $this->organizationsProvided;
  }

  /**
   * Check if organizations should be cleared (removed from all).
   *
   * When value was null we never set primaryOrganization, so it stays NULL.
   *
   * @return bool
   *   TRUE if organizations value was explicitly null.
   */
  public function shouldClearOrganizations(): bool {
    return $this->organizationsProvided && $this->primaryOrganization === NULL;
  }

}
