<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Topic.
 */

use CommerceGuys\Addressing\Address;
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
 * Implements Demo content for Events.
 */
class SocialDemoEvent implements ContainerInjectionInterface {

  protected $events;

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
   * @var \Drupal\Core\Entity\EntityStorageInterface
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
    $this->events = $yml_data->parseFile('entity/event.yml');
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
    foreach ($this->events as $uuid => $event) {
      // Must have uuid and same key value.
      if ($uuid !== $event['uuid']) {
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
      $uid = $accountClass->loadUserFromUuid($event['uid']);

      // Try and fetch the image.
      $media_id = '';
      if (!empty($event['field_event_image'])) {
        $fileClass = new SocialDemoFile();
        $fid = $fileClass->loadByUuid($event['field_event_image']);
        if ($file = File::load($fid)) {
          $media_id = $file->id();
        }
      }

      $start_date = $this->createDate($event['field_event_date']);
      $end_date = $this->createDate($event['field_event_date_end']);

      if (isset($event['field_content_visibility'])) {
        $content_visibility = $event['field_content_visibility'];
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
        'uuid' => $event['uuid'],
        'type' => $event['type'],
        'langcode' => $event['language'],
        'title' => $event['title'],
        'body' => [
          'summary' => '',
          'value' => $event['body'],
          'format' => 'basic_html',
        ],
        'uid' => $uid,
        'created' => REQUEST_TIME,
        'changed' => REQUEST_TIME,
        'field_event_location' => $event['field_event_location'],
        'field_event_image' => $image,
        'field_content_visibility' => [
          [
            'value' => $content_visibility,
          ],
        ],
      ]);
      $node->set('field_event_date', $start_date);
      $node->set('field_event_date_end', $end_date);

      // TODO: Actually make this work.
      $address = new Address();
      $address = $address
        ->withCountryCode($event['field_event_address']['country_code'])
        ->withPostalCode($event['field_event_address']['postal_code'])
        ->withLocality($event['field_event_address']['locality'])
        ->withAddressLine1($event['field_event_address']['address_line1']);

      $node->set('field_event_address', $address);
      $node->save();

      // Check if the referenced group exists.
      if (isset($event['group'])) {
        // Load the group.
        $groups = $this->entityStorage->loadByProperties(array('uuid' => $event['group']));
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
    foreach ($this->events as $uuid => $event) {

      // Must have uuid and same key value.
      if ($uuid !== $event['uuid']) {
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

  /**
   * Function to calculate the date.
   */
  public function createDate($date_string) {
    // Split from delimiter.
    $timestamp = explode('|', $date_string);

    $date = strtotime($timestamp[0]);
    $date = date("Y-m-d", $date) . "T" . $timestamp[1] . ":00";

    return $date;

  }

  /**
   * Load a file object by uuid.
   *
   * @param string $uuid
   *   The uuid of the file.
   *
   * @return int $fid
   *   Returns the file id for the given uuid.
   */
  public function loadByUuid($uuid) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'event');
    $query->condition('uuid', $uuid);
    $query->addMetaData('account', user_load(1)); // Run the query as user 1.
    $fids = $query->execute();
    // Get a single item.
    $fid = reset($fids);
    // And return it.
    return $fid;
  }

}
