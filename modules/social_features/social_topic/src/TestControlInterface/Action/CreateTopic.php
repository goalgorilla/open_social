<?php

declare(strict_types=1);

namespace Drupal\social_topic\TestControlInterface\Action;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\social_topic\TestControlInterface\Dto\CreateTopicRequest;
use Drupal\social_topic\TestControlInterface\Error\TopicNotFound;
use Drupal\social_topic\TestControlInterface\Success\TopicByTitleSuccess;
use Drupal\social_topic\TestControlInterface\Success\TopicCreatedSuccess;
use Drupal\social_topic\TestControlInterface\Success\TopicsBulkCreatedSuccess;
use Drupal\social_topic\TestControlInterface\Success\TopicsWithNonAnonymousAuthorCreatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoints for topic creation (replaces TopicContext bulk steps).
 *
 * Creation logic lives here (under TestControlInterface), so the module does
 * not register extra services outside this subtree.
 */
final class CreateTopic {

  /**
   * Role granting the permission needed to author "visibility by role" content.
   *
   * The social_role_visibility module only allows a
   * `field_content_visibility` value of `visibility_by_role` when the acting
   * user has the role being gated, or holds the `access all role visibility
   * options` permission. The author created for `topics with non-anonymous
   * author:` has no roles, so it needs this permission explicitly; this role
   * exists so we grant exactly that permission and nothing more.
   */
  private const AUTHOR_VISIBILITY_ROLE_ID = 'tci_topic_role_visibility_author';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountSwitcherInterface $accountSwitcher,
  ) {
  }

  /**
   * Retrieve a topic by its title.
   *
   * @param string $title
   *   The title of the topic.
   *
   * @return \Drupal\social_topic\TestControlInterface\Success\TopicByTitleSuccess|\Drupal\social_topic\TestControlInterface\Error\TopicNotFound
   *   The operation result.
   */
  #[Operation(
    id: 'topic_by_title',
    patterns: [
      'topic :title should exist',
      'I get the ID for topic :title',
    ],
    type: OperationType::Read,
  )]
  public function topicByTitle(
    #[Example("Great Topic")]
    string $title,
  ): TopicByTitleSuccess|TopicNotFound {
    $id = $this->getNodeIdFromTitle('topic', $title);

    if ($id === NULL) {
      return new TopicNotFound($title);
    }

    return new TopicByTitleSuccess(nid: $id);
  }

  /**
   * Creates a single topic from a Cucumber table row.
   *
   * @return \Drupal\social_topic\TestControlInterface\Success\TopicCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'topic_create',
    patterns: ['topic:'],
    type: OperationType::Write,
  )]
  public function createSingleTopic(
    #[Example([
      'title' => 'My topic',
      'body' => 'Text',
      'author' => 'admin',
      'field_content_visibility' => 'public',
      'field_topic_type' => 'News',
      'langcode' => 'en',
      'status' => 1,
    ])]
    CreateTopicRequest $body,
  ): TopicCreatedSuccess {
    $node = $this->createTopicFromRow($body->toRowArray());

    return new TopicCreatedSuccess(nid: (int) $node->id());
  }

  /**
   * Creates multiple topics from a table; one row per topic.
   *
   * @param list<CreateTopicRequest> $body
   *   The list of topics to create.
   *
   * @return \Drupal\social_topic\TestControlInterface\Success\TopicsBulkCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'topics_create_bulk',
    patterns: ['topics:'],
    type: OperationType::Write,
  )]
  public function createTopics(
    #[Example([
      [
        'title' => 'A',
        'body' => 'B',
        'author' => 'admin',
        'field_content_visibility' => 'public',
        'field_topic_type' => 'News',
        'langcode' => 'en',
        'status' => 1,
      ],
    ])]
    array $body,
  ): TopicsBulkCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No topic rows in request body; refusing to return success with zero topics created.');
    }
    $nids = [];
    foreach ($body as $topic) {
      $nids[] = $this->createSingleTopic($topic)->nid;
    }

    return new TopicsBulkCreatedSuccess(nids: $nids);
  }

  /**
   * Creates multiple topics with a shared non-anonymous author.
   *
   * @param list<CreateTopicRequest> $body
   *   The list of topics to create.
   *
   * @return \Drupal\social_topic\TestControlInterface\Success\TopicsWithNonAnonymousAuthorCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'topics_create_with_author',
    patterns: ['topics with non-anonymous author:'],
    type: OperationType::Write,
  )]
  public function createTopicsWithNonAnonymousAuthor(
    #[Example([
      [
        'title' => 'An awesome title',
        'body' => 'B',
        'field_content_visibility' => 'public',
        'field_topic_type' => 'News',
        'langcode' => 'en',
        'status' => TRUE,
      ],
    ])]
    array $body,
  ): TopicsWithNonAnonymousAuthorCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No topic rows in request body; refusing to return success with zero topics created.');
    }
    foreach ($body as $topic) {
      if ($topic->author !== NULL) {
        throw new BadRequestHttpException("Can not specify an author when using the 'topics with non-anonymous author:' step, use 'topics:' instead.");
      }
    }

    $needsRoleVisibilityAccess = in_array('visibility_by_role', array_map(
      static fn (CreateTopicRequest $topic): string => $topic->field_content_visibility,
      $body,
    ), TRUE);
    $owner = $this->createRandomAuthenticatedUser($needsRoleVisibilityAccess);
    $author_name = $owner->getAccountName();

    $nids = [];
    foreach ($body as $topic) {
      $row = $topic->withAuthor($author_name)->toRowArray();
      $nids[] = (int) $this->createTopicFromRow($row)->id();
    }

    return new TopicsWithNonAnonymousAuthorCreatedSuccess(nids: $nids, author: $author_name);
  }

  /**
   * Create a topic from a row.
   *
   * @param array<string, mixed> $topic
   *   Row hash: author, title, body, group, field_*, langcode, created, ….
   */
  private function createTopicFromRow(array $topic): Node {
    if (!isset($topic['author'])) {
      throw new BadRequestHttpException('You must specify an `author` when creating a topic. Specify the `author` field if using `topics:` or use `topics with non-anonymous author:` or `topics authored by current user:` instead.');
    }

    $account = $this->loadUserByName((string) $topic['author']);
    if ($account === NULL) {
      throw new BadRequestHttpException(sprintf("User with username '%s' does not exist.", $topic['author']));
    }
    $topic['uid'] = $account->id();
    unset($topic['author']);

    $group_id = NULL;
    if (isset($topic['group'])) {
      $group_id = $this->getGroupIdFromTitle((string) $topic['group']);
      if ($group_id === NULL) {
        throw new BadRequestHttpException(sprintf("Group '%s' does not exist.", $topic['group']));
      }
      unset($topic['group']);
    }

    $topic['type'] = 'topic';

    // The `role_visibility` field and the `visibility_by_role` option on
    // `field_content_visibility` compute their allowed values from the
    // acting user (see
    // social_role_visibility_allowed_role_visibility_options()), not from the
    // entity being created. The TCI endpoint is called unauthenticated, so
    // without impersonating the topic's author here, those allowed-value lists
    // come back empty and validation rejects otherwise-valid values.
    $this->accountSwitcher->switchTo($account);
    try {
      $this->validateEntityFields('node', $topic);
      $topic_object = Node::create($topic);
      $violations = $topic_object->validate();
      if ($violations->count() !== 0) {
        $messages = [];
        foreach ($violations as $violation) {
          $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        }
        throw new BadRequestHttpException('The topic you tried to create is invalid: ' . implode("\n", $messages));
      }
      /** @phpstan-ignore-next-line property.notFound */
      if (!$topic_object->body->format) {
        $topic_object->body->format = 'basic_html';
      }
      $topic_object->save();
    }
    finally {
      $this->accountSwitcher->switchBack();
    }

    if ($group_id !== NULL) {
      try {
        Group::load($group_id)?->addRelationship($topic_object, 'group_node:topic');
      }
      catch (PluginNotFoundException) {
        throw new BadRequestHttpException('Modules that allow adding content to groups should ensure the `gnode` module is enabled.');
      }
    }

    return $topic_object;
  }

  /**
   * Creates a random authenticated user for topic ownership.
   *
   * @param bool $needsRoleVisibilityAccess
   *   Whether the user needs the permission to author content with a
   *   "visibility by role" value; see AUTHOR_VISIBILITY_ROLE_ID.
   */
  private function createRandomAuthenticatedUser(bool $needsRoleVisibilityAccess = FALSE): User {
    $name = substr(bin2hex(random_bytes(8)), 0, 8);
    $mail = $name . '@example.com';
    $user = User::create([
      'name' => $name,
      'mail' => $mail,
      'status' => 1,
    ]);
    if ($needsRoleVisibilityAccess) {
      $user->addRole($this->ensureAuthorVisibilityRole());
    }
    $violations = $user->validate();
    if ($violations->count() > 0) {
      $messages = [];
      foreach ($violations as $violation) {
        $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
      }
      throw new BadRequestHttpException('Invalid user: ' . implode("\n", $messages));
    }
    $user->save();
    return $user;
  }

  /**
   * Ensures AUTHOR_VISIBILITY_ROLE_ID exists and returns its ID.
   *
   * Created lazily rather than via a fixed config/install schema, since it
   * is only ever needed by this Test Control Interface operation.
   *
   * @return string
   *   The role ID.
   */
  private function ensureAuthorVisibilityRole(): string {
    $storage = $this->entityTypeManager->getStorage('user_role');
    if ($storage->load(self::AUTHOR_VISIBILITY_ROLE_ID) === NULL) {
      $role = Role::create([
        'id' => self::AUTHOR_VISIBILITY_ROLE_ID,
        'label' => 'TCI topic author (role visibility)',
      ]);
      $role->grantPermission('access all role visibility options');
      $role->save();
    }

    return self::AUTHOR_VISIBILITY_ROLE_ID;
  }

  /**
   * Validate fields for an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array<string, mixed> $values
   *   The values.
   */
  private function validateEntityFields(string $entity_type, array &$values): void {
    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      throw new BadRequestHttpException('Invalid entity type.');
    }
    $definition = $this->entityTypeManager->getDefinition($entity_type);
    /** @var ?string $bundle */
    $bundle = $definition->getKey('bundle') ?: NULL;
    if ($bundle !== NULL && !isset($values[$bundle])) {
      throw new BadRequestHttpException("Must specify '$bundle' for '$entity_type' type entity.");
    }

    $entityClass = $definition->getClass();
    /** @var \Drupal\Core\Entity\EntityInterface $dummy */
    $dummy = $bundle !== NULL ? $entityClass::create([$bundle => $values[$bundle]]) : $entityClass::create([]);

    foreach (array_keys($values) as $field_name) {
      if ($definition->get($field_name) === NULL && !($dummy instanceof FieldableEntityInterface && $dummy->hasField($field_name))) {
        throw new BadRequestHttpException("Entity type '$entity_type' does not have property or field '$field_name'.");
      }

      $field_definition = ($dummy instanceof FieldableEntityInterface) ? $dummy->getFieldDefinition($field_name) : NULL;
      $this->processFieldValue($field_definition, $field_name, $values);
    }
  }

  /**
   * Dispatch field-type-specific value coercion for a single field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   The field definition, or NULL when '$field_name' is not a field (e.g.
   *   a base entity property).
   * @param string $field_name
   *   The field or property name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processFieldValue(?FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    if ($field_definition === NULL) {
      return;
    }

    switch ($field_definition->getType()) {
      case 'entity_reference':
        $this->processEntityReference($field_definition, $field_name, $values);
        break;

      case 'datetime':
        $this->processDatetime($field_definition, $field_name, $values);
        break;

      case 'timestamp':
      case 'created':
      case 'changed':
        $this->processTimestamp($field_name, $values);
        break;

      case 'list_string':
        if ($field_name === 'field_segment_visibility') {
          $this->processListString($field_definition, $field_name, $values);
        }
        break;
    }
  }

  /**
   * Resolve a taxonomy term or group entity reference field's value.
   *
   * Accepts a comma-separated list of numeric target IDs or entity labels
   * and converts it to the `target_id` array structure Drupal expects.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processEntityReference(FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    $target_type = $field_definition->getSetting('target_type');

    if ($target_type === 'taxonomy_term' && $field_definition->getSetting('handler') === 'default:taxonomy_term') {
      $field_value = $values[$field_name];
      if (!is_string($field_value)) {
        throw new BadRequestHttpException("The taxonomy value for '$field_name' must be a string.");
      }
      if (trim($field_value) === '') {
        unset($values[$field_name]);
        return;
      }
      $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $allowed_bundles = $field_definition->getSetting('handler_settings')['target_bundles'];
      $values[$field_name] = array_map(
        function ($id_or_name) use ($taxonomy_storage, $allowed_bundles) {
          $id_or_name = trim($id_or_name);
          if (is_numeric($id_or_name)) {
            return ['target_id' => $id_or_name];
          }

          $term_ids = $taxonomy_storage->getQuery()
            ->condition('vid', $allowed_bundles, 'IN')
            ->condition('name', $id_or_name)
            ->accessCheck(TRUE)
            ->execute();

          if ($term_ids === []) {
            throw new BadRequestHttpException('Taxonomy term ' . $id_or_name . ' does not exist within vocabulary ' . implode(', ', $allowed_bundles));
          }
          if (count($term_ids) > 1) {
            throw new BadRequestHttpException('Taxonomy term name ' . $id_or_name . ' is ambiguous: ' . count($term_ids) . ' terms share that name within vocabulary ' . implode(', ', $allowed_bundles));
          }
          return ['target_id' => (int) reset($term_ids)];
        },
        explode(',', $field_value)
      );
      return;
    }

    if ($target_type === 'group' && $field_definition->getSetting('handler') === 'default:group') {
      $field_value = $values[$field_name];
      if (!is_string($field_value)) {
        throw new BadRequestHttpException("The group value for '$field_name' must be a string.");
      }
      if (trim($field_value) === '') {
        unset($values[$field_name]);
        return;
      }
      $group_storage = $this->entityTypeManager->getStorage('group');
      $allowed_bundles = $field_definition->getSetting('handler_settings')['target_bundles'];
      $values[$field_name] = array_map(
        function ($id_or_name) use ($group_storage, $allowed_bundles) {
          $id_or_name = trim($id_or_name);
          if (is_numeric($id_or_name)) {
            return ['target_id' => $id_or_name];
          }

          $group_ids = $group_storage->getQuery()
            ->condition('type', $allowed_bundles, 'IN')
            ->condition('label', $id_or_name)
            ->accessCheck(TRUE)
            ->execute();

          if ($group_ids === []) {
            throw new BadRequestHttpException('Group ' . $id_or_name . ' does not exist within type: ' . implode(', ', $allowed_bundles));
          }
          if (count($group_ids) > 1) {
            throw new BadRequestHttpException('Group label ' . $id_or_name . ' is ambiguous: ' . count($group_ids) . ' groups share that label within type: ' . implode(', ', $allowed_bundles));
          }
          return ['target_id' => (int) reset($group_ids)];
        },
        explode(',', $field_value)
      );
    }
  }

  /**
   * Normalize a datetime field's value to Drupal's storage format.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processDatetime(FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    $date = new DrupalDateTime($values[$field_name], 'UTC');
    if ($field_definition->getSetting('datetime_type') === 'date') {
      $values[$field_name] = $date->format('Y-m-d');
    }
    else {
      $values[$field_name] = $date->format('Y-m-d\TH:i:s');
    }
  }

  /**
   * Convert a human-readable date/time string to a Unix timestamp.
   *
   * Covers the `timestamp`, `created` and `changed` field types, which are
   * all stored as a plain Unix timestamp.
   *
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processTimestamp(string $field_name, array &$values): void {
    $timestamp = strtotime((string) $values[$field_name]);
    if ($timestamp === FALSE) {
      throw new BadRequestHttpException(sprintf("The value '%s' for '%s' is not a valid date/time string.", $values[$field_name], $field_name));
    }
    $values[$field_name] = $timestamp;
  }

  /**
   * Resolve the `field_segment_visibility` list_string field's value.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processListString(FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    $field_value = $values[$field_name];
    if (!is_string($field_value)) {
      throw new BadRequestHttpException("The segment visibility value for '$field_name' must be a string.");
    }
    if (trim($field_value) === '') {
      unset($values[$field_name]);
      return;
    }

    $allowed_values_function = $field_definition->getSetting('allowed_values_function');
    if ($allowed_values_function && is_callable($allowed_values_function)) {
      $allowed_values = $allowed_values_function($field_definition);
      $values[$field_name] = array_map(
        function ($segment_label) use ($allowed_values) {
          $segment_label = trim($segment_label);
          $segment_id = array_search($segment_label, $allowed_values);
          if ($segment_id === FALSE) {
            throw new BadRequestHttpException("Segment '$segment_label' does not exist or is not available for visibility.");
          }
          return $segment_id;
        },
        explode(',', $field_value)
      );
    }
  }

  /**
   * Load a user by name.
   *
   * @param string $name
   *   The username.
   *
   * @return \Drupal\user\Entity\User|null
   *   The user or NULL if not found.
   */
  private function loadUserByName(string $name): ?User {
    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
    $first = reset($users);
    return $first instanceof User ? $first : NULL;
  }

  /**
   * Get the group ID for a group by title.
   *
   * @param string $group_title
   *   The group title.
   *
   * @return int|null
   *   The group ID or null if not found.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if more than one group shares the given title, since there is
   *   then no way to deterministically pick the intended group.
   */
  private function getGroupIdFromTitle(string $group_title): ?int {
    $group_ids = $this->entityTypeManager->getStorage('group')->getQuery()
      ->accessCheck(FALSE)
      ->condition('label', $group_title)
      ->execute();
    if ($group_ids === []) {
      return NULL;
    }
    if (count($group_ids) > 1) {
      throw new BadRequestHttpException(sprintf("Group title '%s' is ambiguous: %d groups share that title.", $group_title, count($group_ids)));
    }
    return (int) reset($group_ids);
  }

  /**
   * Get the node from a bundle and title.
   *
   * @param string $bundle
   *   The bundle of the node.
   * @param string $title
   *   The title of the node.
   *
   * @return int|null
   *   The integer ID of the node or NULL if no node could be found.
   */
  private function getNodeIdFromTitle(string $bundle, string $title) : ?int {
    $node_storage = $this->entityTypeManager
      ->getStorage('node');
    $query = $node_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $bundle)
      ->condition('title', $title);

    $node_ids = $query->execute();

    // There must be exactly one matching node otherwise we may get false
    // positives or flaky tests by matching one node from duplicates.
    return count($node_ids) === 1
      ? (int) reset($node_ids)
      : NULL;
  }

}
