<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use OpenSocial\TestBridge\Attributes\Command;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class CommentBridge {

  use EntityTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Create multiple comments.
   *
   * @param array $comments
   *   The comment information that'll be passed to Comment::create().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the comments successfully created and an array of
   *   errors for failures.
   */
  #[Command(name: "create-comments")]
  public function createComments(array $comments) {
    $created = [];
    $errors = [];
    foreach ($comments as $inputId => $comment) {
      try {
        $comment = $this->commentCreate($comment);
        $created[$inputId] = $comment->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
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
