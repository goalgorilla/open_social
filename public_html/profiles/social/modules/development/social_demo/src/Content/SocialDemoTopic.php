<?php

/**
* @file
* Contains \Drupal\social_demo\SocialDemoTopic.
*/

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Topic.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorageInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SocialDemoTopic implements ContainerInjectionInterface {

  private $topics;

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

  /*
   * Read file contents on construction.
   */
  public function __construct(UserStorageInterface $user_storage, NodeStorageInterface $node_storage) {
    $this->userStorage = $user_storage;
    $this->nodeStorage = $node_storage;

    $yml_data = new SocialDemoParser();
    $this->topics = $yml_data->parseFile('entity/topic.yml');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /*
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach($this->topics as $uuid => $topic) {
      // Must have uuid and same key value.
      if ($uuid !== $topic['uuid']) {
        var_dump('Node with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Check if the node does not exist yet
      $nodes = $this->nodeStorage->loadByProperties(array('uuid' => $uuid));
      $node = reset($nodes);

      // If it already exists, leave it.
      if ($node) {
        var_dump('Node with uuid: ' . $uuid . ' already exists.');
        continue;
      }

      // Try and fetch the user.
      $container = \Drupal::getContainer();
      $accountClass = SocialDemoUser::create($container);
      $uid = $accountClass->loadUserFromUuid($topic['uid']);
      // Try and fetch the image.
      $fileClass = new SocialDemoFile();
      $fid = $fileClass->loadByUuid($topic['field_topic_image']);

      $media_id = '';
      if ($file = File::load($fid)) {
        $media_id = $file->id();
      }

      // Let's create some nodes.
      $node = Node::create([
        'uuid' => $topic['uuid'],
        'type' => $topic['type'],
        'langcode' => $topic['language'],
        'title' => $topic['title'],
        'body' => [
          'summary' => '',
          'value' => $topic['body'],
          'format' => 'full_html',
        ],
        'field_topic_type' => [
          [
            'value' => $topic['field_topic_type'],
          ],
        ],
        'field_topic_image' => [
          [
            'target_id' => $media_id,
          ],
        ],
        'uid' => $uid,

        'created' => REQUEST_TIME,
        'changed' => REQUEST_TIME,
      ]);

      $node->save();

      $content_counter++;
    }

    return $content_counter;
  }

  /*
   * Function to remove content.
   */
  public function removeContent() {
    // Loop through the content and try to create new entries.
    foreach($this->topics as $uuid => $topic) {

      // Must have uuid and same key value.
      if ($uuid !== $topic['uuid']) {
        continue;
      }

      // Load the nodes from the uuid.
      $nodes = $this->nodeStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the nodes.
      foreach($nodes as $key => $node) {
        // And delete them.
        $node->delete();
      }
    }
  }

}
