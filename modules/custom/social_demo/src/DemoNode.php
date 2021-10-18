<?php

namespace Drupal\social_demo;

use Drupal\Core\Entity\EntityBase;
use Drupal\flag\Entity\Flagging;
use Drupal\node\NodeInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Abstract class for creating demo nodes.
 *
 * @package Drupal\social_demo
 */
abstract class DemoNode extends DemoContent {

  /**
   * {@inheritdoc}
   */
  public function createContent($generate = FALSE, $max = NULL) {
    $data = $this->fetchData();
    if ($generate === TRUE) {
      $data = $this->scrambleData($data, $max);
    }

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        $this->loggerChannelFactory->get('social_demo')->error("Node with uuid: {$uuid} has a different uuid in content.");
        continue;
      }

      // Check whether node with same uuid already exists.
      $nodes = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if (reset($nodes)) {
        $this->loggerChannelFactory->get('social_demo')->warning("Node with uuid: {$uuid} already exists.");
        continue;
      }

      // Try to load a user account (author's account).
      $account = $this->loadByUuid('user', $item['uid']);

      if (!$account) {
        $this->loggerChannelFactory->get('social_demo')->error("Account with uuid: {$item['uid']} doesn't exists.");
        continue;
      }

      // Create array with data of a node.
      $item['uid'] = $account->id();

      if (isset($item['created'])) {
        $item['created'] = $this->createDate($item['created']);
      }
      else {
        $item['created'] = \Drupal::time()->getRequestTime();
      }

      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[$entity->id()] = $entity;

        if (!empty($item['group'])) {
          $this->createGroupContent($entity, $item['group']);
        }

        if (isset($item['followed_by'])) {
          $this->createFollow($entity, $item['followed_by']);
        }
      }
    }

    return $this->content;
  }

  /**
   * Converts a date in the correct format.
   *
   * @param string $date_string
   *   The date.
   *
   * @return int|false
   *   Returns a timestamp on success, false otherwise.
   */
  protected function createDate($date_string) {
    if ($date_string === 'now') {
      return time();
    }
    // Split from delimiter.
    $timestamp = explode('|', $date_string);

    $date = strtotime($timestamp[0]);
    $date = date('Y-m-d', $date) . 'T' . $timestamp[1] . ':00';

    return strtotime($date);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry(array $item) {
    $entry = [
      'uuid' => $item['uuid'],
      'langcode' => $item['langcode'],
      'created' => $item['created'],
      'uid' => $item['uid'],
      'title' => $item['title'],
      'type' => $item['type'],
      'body' => [
        'value' => $this->checkMentionOrLinkByUuid($item['body']),
        'format' => 'basic_html',
      ],
    ];

    return $entry;
  }

  /**
   * Creates a group content.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   Object of a node.
   * @param string $uuid
   *   UUID of a group.
   */
  public function createGroupContent(NodeInterface $entity, $uuid) {
    // Load the group.
    $groups = $this->groupStorage->loadByProperties([
      'uuid' => $uuid,
    ]);

    if ($groups) {
      $group = current($groups);
      // Get the group content plugin.
      $plugin_id = 'group_node:' . $entity->bundle();
      $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
      $group_content = GroupContent::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
        'entity_id' => $entity->id(),
      ]);
      $group_content->save();
    }
  }

  /**
   * The function that checks and creates a follow on an entity.
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   The related entity.
   * @param array $uuids
   *   The array containing uuids.
   */
  public function createFollow(EntityBase $entity, array $uuids) {

    foreach ($uuids as $uuid) {
      // Load the user(s) by the given uuid(s).
      $account = $this->loadByUuid('user', $uuid);

      $properties = [
        'uid' => $account->id(),
        'entity_type' => 'node',
        'entity_id' => $entity->id(),
        'flag_id' => 'follow_content',
      ];

      // Check the current flaggings.
      $flaggings = \Drupal::entityTypeManager()
        ->getStorage('flagging')
        ->loadByProperties($properties);
      $flagging = reset($flaggings);

      // If the user is already following, then nothing happens.
      // Else we create the flagging with the properties above.
      if (empty($flagging)) {
        $flagging = Flagging::create($properties);
        if ($flagging) {
          $flagging->save();
        }
      }
    }

  }

  /**
   * Scramble it.
   *
   * @param array $data
   *   The data array to scramble.
   * @param int|null $max
   *   How many items to generate.
   */
  public function scrambleData(array $data, $max = NULL) {
    $new_data = [];
    for ($i = 0; $i < $max; $i++) {
      // Get a random item from the array.
      $old_uuid = array_rand($data);
      $item = $data[$old_uuid];
      $uuid = 'ScrambledDemo_' . time() . '_' . $i;
      $item['uuid'] = $uuid;
      $item['title'] = $uuid;
      $item['body'] = $uuid;
      $item['created'] = '-' . random_int(1, 2 * 365) . ' day|' . random_int(0, 23) . ':' . random_int(0, 59);
      $new_data[$uuid] = $item;
    }
    return $new_data;
  }

}
