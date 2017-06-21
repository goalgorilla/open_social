<?php

namespace Drupal\social_demo;

use Drupal\user\UserStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Drupal\group\Entity\GroupContent;
use Drush\Log\LogLevel;

abstract class DemoNode extends DemoContent {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $groupStorage;

  /**
   * DemoNode constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\social_demo\DemoContentParserInterface $parser
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\Core\Entity\EntityStorageInterface $group_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, UserStorageInterface $user_storage, EntityStorageInterface $group_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
    $this->groupStorage = $group_storage;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_demo.yaml_parser'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createContent() {
    $data = $this->fetchData();

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        drush_log(dt("Node with uuid: {$uuid} has a different uuid in content."), LogLevel::ERROR);
        continue;
      }

      // Check whether node with same uuid already exists.
      $nodes = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if (reset($nodes)) {
        drush_log(dt("Node with uuid: {$uuid} already exists."), LogLevel::WARNING);
        continue;
      }

      // Try to load a user account (author's account).
      $account = $this->loadByUuid('user', $item['uid']);

      if (!$account) {
        drush_log(dt("Account with uuid: {$item['uid']} doesn't exists."), LogLevel::ERROR);
        continue;
      }

      // Create array with data of a node.
      $item['uid'] = $account->id();

      if (isset($item['created'])) {
        $item['created'] = strtotime($item['created']);
      }
      else {
        $item['created'] = REQUEST_TIME;
      }

      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[ $entity->id() ] = $entity;

        if (!empty($item['group'])) {
          $this->createGroupContent($entity, $item['group']);
        }
      }
    }

    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry($item) {
    $entry = [
      'uuid' => $item['uuid'],
      'langcode' => $item['langcode'],
      'created' => $item['created'],
      'uid' => $item['uid'],
      'title' => $item['title'],
      'type' => $item['type'],
      'body' => [
        'value' => $item['body'],
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

}
