<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Likes.
 */
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\social_post\Entity\Post;
use Drupal\user\Entity\User;
use Drupal\votingapi\Entity\Vote;
use Drupal\node\NodeStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Demo content for Event Enrollments.
 */
class SocialDemoLikes implements ContainerInjectionInterface {

  protected $votes;

  /**
   * The post storage.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $entityStorage;
  /**
   * The vote storage.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $voteStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The post storage.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Read file contents on construction.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   * @param \Drupal\node\NodeStorageInterface $node_storage
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\Core\Entity\EntityStorageInterface $vote_storage
   */
  public function __construct(EntityStorageInterface $entity_storage, NodeStorageInterface $node_storage, UserStorageInterface $user_storage, EntityStorageInterface $vote_storage) {

    $this->entityStorage = $entity_storage;
    $this->nodeStorage = $node_storage;
    $this->userStorage = $user_storage;
    $this->voteStorage = $vote_storage;

    $yml_data = new SocialDemoParser();
    $this->votes = $yml_data->parseFile('entity/likes.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('post'),
      $container->get('entity.manager')->getStorage('node'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('vote')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;
    // Loop through the content and try to create new entries.
    foreach ($this->votes as $uuid => $vote) {

      // Check for existing votes.
      $check_votes = $this->voteStorage->loadByProperties(['uuid' => $uuid]);
      $check_vote = reset($check_votes);

      // If it already exists, leave it.
      if ($check_vote) {
        var_dump('Vote with uuid: ' . $uuid . ' already exists.');
        continue;
      }

      switch ($vote['entity_type']) {
        case 'post':
          $entity = $this->entityStorage->loadByProperties(['uuid' => $vote['entity_id']]);
          break;
        case 'node':
          $entity = $this->nodeStorage->loadByProperties(['uuid' => $vote['entity_id']]);
          break;
      }

      // Pop the array.
      $entity = reset($entity);

      // Post or Node.
      $entity_id = NULL;
      if ($entity instanceof Post || $entity instanceof Node) {
        $entity_id = $entity->id();
      }

      $user = $this->userStorage->loadByProperties(array('uuid' => $vote['user_id']));
      $user = reset($user);

      // User.
      $user_id = NULL;
      if ($user instanceof User) {
        $user_id = $user->id();
      }

      // If we have both an entity_id and a user_id we can continue
      if (!empty($entity_id) && !empty($user_id)) {
        // Create likes.
        $like = Vote::create([
          'type' => 'like',
          'uuid' => $uuid,
          'entity_type' => $vote['entity_type'],
          'entity_id' => $entity_id,
          'value' => 1,
          'value_type' => 'points',
          'user_id' => $user_id,
          'timestamp' => REQUEST_TIME,
          'vote_source' => 'localhost'
        ]);
        $like->save();

        $content_counter++;
      }
    }
    return $content_counter;
  }

  /**
   * Function to remove content.
   */
  public function removeContent() {
    $content_counter = 0;
    // Loop through the content and try to create new entries.
    foreach ($this->votes as $uuid => $vote) {
      // Must have uuid and same key value.
      if ($uuid !== $vote['uuid']) {
        continue;
      }
      // Load the nodes from the uuid.
      $entities = $this->voteStorage->loadByProperties(['uuid' => $uuid]);
      // Loop through the nodes.
      foreach ($entities as $key => $entity) {
        // And delete them.
        $entity->delete();
        $content_counter++;
      }
    }
    return $content_counter;
  }
}
