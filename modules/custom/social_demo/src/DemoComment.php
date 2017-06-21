<?php

namespace Drupal\social_demo;

use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drush\Log\LogLevel;

abstract class DemoComment extends DemoContent {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * DemoComment constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\social_demo\DemoContentParserInterface $parser
   * @param \Drupal\user\UserStorageInterface $user_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, UserStorageInterface $user_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
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
      $container->get('entity.manager')->getStorage('user')
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
        drush_log(dt("Comment with uuid: {$uuid} has a different uuid in content."), LogLevel::ERROR);
        continue;
      }

      // Check whether comment with same uuid already exists.
      $comments = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($comments) {
        drush_log(dt("Comment with uuid: {$uuid} already exists."), LogLevel::WARNING);
        continue;
      }

      // Try to load a user account (author's account).
      $accounts = $this->userStorage->loadByProperties([
        'uuid' => $item['uid'],
      ]);

      if (!$accounts) {
        drush_log(dt("Account with uuid: {$item['uid']} doesn't exists."), LogLevel::ERROR);
        continue;
      }

      $account = current($accounts);

      // Create array with data of a comment.
      $item['uid'] = $account->id();
      $item['pid'] = NULL;

      // Set parent comment if it is present
      if (!empty($item['parent'])) {
        $comments = $this->entityStorage->loadByProperties([
          'uuid' => $item['parent'],
        ]);

        if ($comments) {
          $comment = current($comments);
          $item['pid'] = $comment->id();
        }
      }

      // Try and fetch the related entity.
      $entity = $this->loadByUuid($item['entity_type'], $item['entity_id']);

      if (!$entity) {
        drush_log(dt("Entity {$item['entity_type']} with uuid: {$item['entity_id']} doesn't exists."), LogLevel::ERROR);
        continue;
      }

      $item['created'] += $entity->get('created')->value;

      if ($item['created'] > REQUEST_TIME) {
        $item['created'] = REQUEST_TIME;
      }

      $item['entity_id'] = $entity->id();
      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[ $entity->id() ] = $entity;
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
      'field_comment_body' => [
        [
          'value' => $item['body'],
          'format' => 'basic_html',
        ],
      ],
      'langcode' => $item['langcode'],
      'uid' => $item['uid'],
      'entity_id' => $item['entity_id'],
      'pid' => $item['pid'],
      'created' => $item['created'],
      'changed' => $item['created'],
      'field_name' => $item['field_name'],
      'comment_type' => $item['type'],
      'entity_type' => $item['entity_type'],
      'status' => 1,
    ];

    return $entry;
  }

}
