<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Topic.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorageInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Demo content for Topics.
 */
class SocialDemoTopic implements ContainerInjectionInterface {

  protected $topics;

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
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface;
   */
  protected $entityStorage;

  /**
   * Read file contents on construction.
   */
  public function __construct(UserStorageInterface $user_storage, NodeStorageInterface $node_storage, EntityStorageInterface $entity_storage) {
    $this->userStorage = $user_storage;
    $this->nodeStorage = $node_storage;
    $this->entityStorage = $entity_storage;

    $yml_data = new SocialDemoParser();
    $this->topics = $yml_data->parseFile('entity/topic.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('node'),
      $container->get('entity.manager')->getStorage('group')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach ($this->topics as $uuid => $topic) {
      // Must have uuid and same key value.
      if ($uuid !== $topic['uuid']) {
        var_dump('Node with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Check if the node does not exist yet.
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
      $media_id = '';
      if (!empty($topic['field_topic_image'])) {
        $fileClass = new SocialDemoFile();
        $fid = $fileClass->loadByUuid($topic['field_topic_image']);
        if ($file = File::load($fid)) {
          $media_id = $file->id();
        }
      }

      // Determine topic type.
      $term = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(array('name' => $topic['field_topic_type']));
      $term = array_pop($term);
      $topic_type_target = $term->id();

      if (isset($topic['field_content_visibility'])) {
        $content_visibility = $topic['field_content_visibility'];
      }
      else {
        $content_visibility = 'community';
      }

      $image = array();
      if (!empty($media_id)) {
        $image = array (
            'target_id' => $media_id,
        );
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
          'format' => 'basic_html',
        ],
        'field_topic_type' => [
          [
            'target_id' => $topic_type_target,
          ],
        ],
        'field_topic_image' => $image,
        'field_content_visibility' => [
          [
            'value' => $content_visibility,
          ],
        ],
        'uid' => $uid,

        'created' => REQUEST_TIME,
        'changed' => REQUEST_TIME,
      ]);

      $node->save();

      // Check if the referenced group exists.
      if (isset($topic['group'])) {
        // Load the group.
        $groups = $this->entityStorage->loadByProperties(array('uuid' => $topic['group']));
        $group = reset($groups);
        // Get the group content plugin.
        $plugin_id = 'group_node:' . $node->bundle();
        $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
        // Create the group content entity.
        $group_content = GroupContent::create([
          'type' => $plugin->getContentTypeConfigId(),
          'gid' => $group->id(),
          'entity_id' => $node->id(),
        ]);
        // Save it.
        $group_content->save();
      }

      $content_counter++;
    }

    return $content_counter;
  }

  /**
   * Function to remove content.
   */
  public function removeContent() {
    // Loop through the content and try to create new entries.
    foreach ($this->topics as $uuid => $topic) {

      // Must have uuid and same key value.
      if ($uuid !== $topic['uuid']) {
        continue;
      }

      // Load the nodes from the uuid.
      $nodes = $this->nodeStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the nodes.
      foreach ($nodes as $key => $node) {
        // And delete them.
        $node->delete();
      }
    }
  }

}
