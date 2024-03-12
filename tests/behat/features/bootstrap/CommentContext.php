<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Defines test steps around the usage of comments.
 */
class CommentContext extends RawMinkContext {

  use EntityTrait;

  /**
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * Create multiple comments at the start of a test.
   *
   * ```
   * Given comments:
   *   | target_type | target_label               | author    | status | subject                  | field_comment_body           |
   *   | node:topic  | Some Topic                 | username  | 1      | The comment subject      | This is a really cool topic! |
   *   | node        | A node with unknown bundle | username2 | 1      | Not shown in Open Social | Can you elaborate?           |
   * ```
   *
   * @Given comments:
   */
  public function createComments(TableNode $commentsTable) : void {
    foreach ($commentsTable->getHash() as $commentHash) {
      $comment = $this->commentCreate($commentHash);
      $this->created[] = $comment->id();
    }
  }

  /**
   * Create multiple comments at the start of a test.
   *
   * ```
   * Given comments:
   *   | target_type | target_label               | status | subject                  | field_comment_body           |
   *   | node:topic  | Some Topic                 | 1      | The comment subject      | This is a really cool topic! |
   *   | node        | A node with unknown bundle | 1      | Not shown in Open Social | Can you elaborate?           |
   * ```
   *
   * @Given comments with non-anonymous author:
   */
  public function createCommentsWithAuthor(TableNode $commentsTable) : void {
    // Create a new random user to own the content, this ensures the author
    // isn't anonymous.
    $user = (object) [
      'name' => $this->drupalContext->getRandom()->name(8),
      'pass' => $this->drupalContext->getRandom()->name(16),
      'role' => "authenticated",
    ];
    $user->mail = "{$user->name}@example.com";

    $this->drupalContext->userCreate($user);

    foreach ($commentsTable->getHash() as $commentHash) {
      if (isset($commentHash['author'])) {
        throw new \Exception("Can not specify an author when using the 'comments with non-anonymous owner:' step, use 'comments:' instead.");
      }

      $commentHash['author'] = $user->name;

      $comment = $this->commentCreate($commentHash);
      $this->created[] = $comment->id();
    }
  }

  /**
   * Create multiple comments at the start of a test.
   *
   * ```
   * Given comments:
   *   | target_type | target_label               | status | subject                  | field_comment_body           |
   *   | node:topic  | Some Topic                 | 1      | The comment subject      | This is a really cool topic! |
   *   | node        | A node with unknown bundle | 1      | Not shown in Open Social | Can you elaborate?           |
   * ```
   *
   * @Given comments authored by current user:
   */
  public function createCommentsAuthoredByCurrentUser(TableNode $commentsTable) : void {
    $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
    foreach ($commentsTable->getHash() as $commentHash) {
      if (isset($commentHash['author'])) {
        throw new \Exception("Can not specify an author when using the 'comments authored by current user:' step, use 'comments:' instead.");
      }

      $commentHash['author'] = (is_object($current_user) ? $current_user->name : NULL) ?? 'anonymous';

      $comment = $this->commentCreate($commentHash);
      $this->created[] = $comment->id();
    }
  }

  /**
   * Create a comment.
   *
   * @return \Drupal\comment\CommentInterface
   *   The created comment
   */
  private function commentCreate($comment) : CommentInterface {
    if (!isset($comment['target_type'])) {
      throw new \Exception("You must specify a `target_type` when creating a comment. Provide either an entity_type_id such as `node` or an entity_type_id:bundle such as `node:topic`.");
    }
    if (!isset($comment['target_label'])) {
      throw new \Exception("You must specify a `target_label` when creating a comment, containing the title of the entity you're commenting on.");
    }
    if (!isset($comment['author'])) {
      throw new \Exception("You must specify an `author` when creating a comment. Specify the `author` field if using `@Given comments:` or use one of `@Given comments with non-anonymous author:` or `@Given comments authored by current user:` instead.");
    }

    $account = user_load_by_name($comment['author']);
    if ($account === FALSE) {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $comment['author']));
    }
    $comment['uid'] = $account->id();
    unset($comment['author']);

    // The NULL array ensures `target_entity_bundle` doesn't cause warnings if
    // the target type doesn't have a bundle specified.
    [$target_entity_type, $target_entity_bundle] = explode(":", $comment['target_type']) + [NULL, NULL];
    $comment['entity_id'] = $this->getEntityIdFromLabel($target_entity_type, $target_entity_bundle, $comment['target_label']);
    assert($comment['entity_id'] !== NULL, "Could not find $target_entity_type with label '{$comment['target_label']}'.");
    $comment['entity_type'] = $target_entity_type;
    unset($comment['target_type'], $comment['target_label']);

    if (isset($comment['parent_subject']) && $comment['parent_subject'] !== '') {
      $parent_id = $this->getEntityIdFromLabel('comment', NULL, $comment['parent_subject']);
      if ($parent_id === NULL) {
        throw new \Exception("Could not find comment with subject '{$comment['parent_subject']}'.");
      }

      $comment['pid'][] = ['target_id' => $parent_id];
    }
    unset($comment['parent_subject']);

    if (!isset($comment['field_name']) || $comment['field_name'] === '') {
      if ($target_entity_bundle === NULL) {
        throw new \Exception("Must either specify the bundle as part of `target_type` or specify `field_name` separately, neither provided.");
      }
      $field_name = $this->getCommentFieldForEntity($target_entity_type, $target_entity_bundle);
      if ($field_name === NULL) {
        throw new \Exception("Could not find comment field on $target_entity_type for bundle $target_entity_bundle. If the field exists specify it manually using `field_name`.");
      }
      $comment['field_name'] = $field_name;
    }

    $this->validateEntityFields("comment", $comment);
    $comment_object = Comment::create($comment);
    $violations = $comment_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The comment you tried to create is invalid: $violations");
    }
    $comment_object->save();

    return $comment_object;
  }

  /**
   * Get an entity from its type and title.
   *
   * @param string $type
   *   The entity type to load.
   * @param string|null $bundle
   *   The bundle of the entity to limit results to or NULL to skip.
   * @param string $label
   *   The title of the entity.
   *
   * @return int|null
   *   The integer ID of the entity or NULL if no matching entity could be
   *   found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *    Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *    Thrown if the storage handler couldn't be loaded.
   */
  private function getEntityIdFromLabel(string $type, ?string $bundle, string $label) : ?int {
    $storage = \Drupal::entityTypeManager()->getStorage($type);
    $entity_type = $storage->getEntityType();

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition($entity_type->getKey('label'), $label);

    if ($bundle !== NULL) {
      $query->condition($entity_type->getKey('bundle'), $bundle);
    }

    $entity_ids = $query->execute();

    if (count($entity_ids) !== 1) {
      return NULL;
    }

    return (int) reset($entity_ids);
  }

  /**
   * Get the comment field for an entity bundle.
   *
   * The comment entity requires specifying in which field the comment was
   * created.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return string|null
   *   The field name or NULL if none was found.
   */
  private function getCommentFieldForEntity(string $type, string $bundle) : ?string {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = \Drupal::getContainer()->get('entity_field.manager');
    foreach ($field_manager->getFieldDefinitions($type, $bundle) as $definition) {
      if ($definition->getType() === 'comment') {
        return $definition->getName();
      }
    }
    return NULL;
  }

}
