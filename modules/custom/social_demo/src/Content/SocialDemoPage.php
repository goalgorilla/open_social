<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Page.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Demo content for Pages.
 */
class SocialDemoPage implements ContainerInjectionInterface {

  protected $pages;

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
   * Read file contents on construction.
   */
  public function __construct(UserStorageInterface $user_storage, NodeStorageInterface $node_storage) {
    $this->userStorage = $user_storage;
    $this->nodeStorage = $node_storage;

    $yml_data = new SocialDemoParser();
    $this->pages = $yml_data->parseFile('entity/page.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {
    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach ($this->pages as $uuid => $page) {
      // Must have uuid and same key value.
      if ($uuid !== $page['uuid']) {
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

      $uid = $page['uid'];

      if (!is_numeric($uid)) {
        // Try and fetch the user.
        $container = \Drupal::getContainer();
        $accountClass = SocialDemoUser::create($container);
        $uid = $accountClass->loadUserFromUuid($uid);
      }

      if (isset($page['field_content_visibility'])) {
        $content_visibility = $page['field_content_visibility'];
      }
      else {
        $content_visibility = 'community';
      }

      // Create entity.
      $entry = [
        'uuid' => $page['uuid'],
        'type' => $page['type'],
        'langcode' => $page['language'],
        'title' => $page['title'],
        'body' => [
          'summary' => '',
          'value' => $page['body'],
          'format' => 'basic_html',
        ],
        'field_content_visibility' => [
          [
            'value' => $content_visibility,
          ],
        ],
        'uid' => $uid,
        'created' => REQUEST_TIME,
        'changed' => REQUEST_TIME,
      ];

      if (!empty($page['alias'])) {
        $entry['path'] = [
          'alias' => $page['alias'],
        ];
      }

      $node = $this->nodeStorage->create($entry);
      $node->save();

      $content_counter++;
    }

    return $content_counter;
  }

  /**
   * Function to remove content.
   */
  public function removeContent() {
    // Loop through the content and try to create new entries.
    foreach ($this->pages as $uuid => $page) {

      // Must have uuid and same key value.
      if ($uuid !== $page['uuid']) {
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
