<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Comment.
 */

use Drupal\comment\Entity\Comment;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\social_post\Entity\Post;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

/**
 * Implements Demo content for Comments.
 */
class SocialDemoComment implements ContainerInjectionInterface {

  protected $comments;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $commentStorage;

  /**
   * Read file contents on construction.
   */
  public function __construct(UserStorageInterface $user_storage, NodeStorageInterface $node_storage, EntityStorageInterface $entity_storage) {
    $this->userStorage = $user_storage;
    $this->nodeStorage = $node_storage;
    $this->commentStorage = $entity_storage;

    $yml_data = new SocialDemoParser();
    $this->comments = $yml_data->parseFile('entity/comment.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('node'),
      $container->get('entity.manager')->getStorage('comment')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach ($this->comments as $uuid => $content) {
      // Must have uuid and same key value.
      if ($uuid !== $content['uuid']) {
        var_dump('Comment with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Check if the entity does not exist yet.
      $comments = $this->commentStorage->loadByProperties(array('uuid' => $uuid));
      $comment = reset($comments);

      // If it already exists, leave it.
      if ($comment) {
        var_dump('Comment with uuid: ' . $uuid . ' already exists.');
        continue;
      }

      // Try and fetch the user.
      $container = \Drupal::getContainer();
      $accountClass = SocialDemoUser::create($container);
      $uid = $accountClass->loadUserFromUuid($content['uid']);

      if ($content['comment_type'] === 'comment') {
        $entity_type = 'node';
        // Try and fetch the related node.
        $nid = $this->fetchRelatedNode($content['entity_id']);

        // Load that node.
        $node = Node::load($nid);

        if (!is_object($node)) {
          var_dump('Target node with nid ' . $nid . ' could not be loaded.');
          continue;
        }

        // Determine the field in which is should be put.
        $comment_field_type = 'field_' . $node->getType() . '_comments';
        // Add time.
        $content['created'] += $node->getCreatedTime();
      }
      elseif ($content['comment_type'] === 'post_comment') {
        $entity_type = 'post';
        // Try and fetch the related node.
        $nid = $this->fetchRelatedEntity($content['entity_id'], $entity_type);
        // Load that node.
        $entity = Post::load($nid);
        if (!is_object($entity)) {
          var_dump('Target entity with nid ' . $nid . ' could not be loaded.');
          continue;
        }

        // Determine the field in which is should be put.
        $comment_field_type = 'field_' . $entity_type . '_comments';
        // Add time.
        $entity_created = $entity->get('created')->getValue();
        $content['created'] += $entity_created[0]['value'];
      }

      // Check if a parent comment id is set!
      $pid = NULL;
      if (isset($content['pid'])) {
        // Try to load the parent comment.
        $parent_comments = $this->commentStorage->loadByProperties(array('uuid' => $content['pid']));
        $parent_comment = reset($parent_comments);
        // Store the parent comment id.
        $pid = $parent_comment->id();
      }

      if ($content['created'] > time()) {
        // Make sure comments are not posted in the future.
        $content['created'] = time();
      }


      // Let's create some nodes.
      $comment = Comment::create([
        'uuid' => $content['uuid'],
        'field_comment_body' => $content['body'],
        'langcode' => 'und',
        'uid' => $uid,
        'entity_id' => $nid,
        'pid' => $pid,

        'created' => $content['created'],
        'changed' => $content['created'],

        'field_name' => $comment_field_type,
        'comment_type' => $content['comment_type'],
        'entity_type' => $entity_type,
        'status' => 1,
      ]);

      $comment->save();

      $content_counter++;
    }

    return $content_counter;
  }

  /**
   * Function to remove content.
   */
  public function removeContent() {
    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach ($this->comments as $uuid => $content) {

      // Must have uuid and same key value.
      if ($uuid !== $content['uuid']) {
        continue;
      }

      // Load the nodes from the uuid.
      $comments = $this->commentStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the nodes.
      foreach ($comments as $key => $comment) {
        // And delete them.
        $comment->delete();

        // Count it.
        $content_counter++;
      }
    }

    return $content_counter;
  }

  /**
   * Load a node object by uuid.
   *
   * @param string $uuid
   *   The uuid of the node.
   *
   * @return int $fid
   *   Returns the nid for the related nodes.
   */
  public function fetchRelatedNode($uuid) {
    $query = \Drupal::entityQuery('node');
    $query->condition('uuid', $uuid);
    $query->addMetaData('account', user_load(1)); // Run the query as user 1.
    $nids = $query->execute();

    // Get a single item.
    $nid = reset($nids);
    // And return it.
    return $nid;
  }

  /**
   * Load a node object by uuid.
   *
   * @param string $uuid
   *   The uuid of the node.
   *
   * @return int $fid
   *   Returns the nid for the related nodes.
   */
  public function fetchRelatedEntity($uuid, $entity_type) {
    $query = \Drupal::entityQuery($entity_type);
    $query->condition('uuid', $uuid);
    $query->addMetaData('account', user_load(1)); // Run the query as user 1.
    $entities = $query->execute();

    // Get a single item.
    $entity_id = reset($entities);
    // And return it.
    return $entity_id;
  }

}
