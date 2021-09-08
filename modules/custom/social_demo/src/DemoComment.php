<?php

namespace Drupal\social_demo;

use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class for generating demo comments.
 *
 * @package Drupal\social_demo
 */
abstract class DemoComment extends DemoContent {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected LoggerChannelFactory $loggerChannelFactory;

  /**
   * DemoComment constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DemoContentParserInterface $parser,
    UserStorageInterface $user_storage,
    LoggerChannelFactory $logger_channel_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
    $this->userStorage = $user_storage;
    $this->loggerChannelFactory = $logger_channel_factory;
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
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('logger.factory')
    );
  }

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
        $this->loggerChannelFactory->get('social_demo')->error("Comment with uuid: {$uuid} has a different uuid in content.");
        continue;
      }

      // Check whether comment with same uuid already exists.
      $comments = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($comments) {
        $this->loggerChannelFactory->get('social_demo')->warning("Comment with uuid: {$uuid} already exists.");
        continue;
      }

      // Try to load a user account (author's account).
      $accounts = $this->userStorage->loadByProperties([
        'uuid' => $item['uid'],
      ]);

      if (!$accounts) {
        $this->loggerChannelFactory->get('social_demo')->error("Account with uuid: {$item['uid']} doesn't exists.");
        continue;
      }

      $account = current($accounts);

      // Create array with data of a comment.
      $item['uid'] = $account->id();
      $item['pid'] = NULL;

      // Set parent comment if it is present.
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
        $this->loggerChannelFactory->get('social_demo')->error("Entity {$item['entity_type']} with uuid: {$item['entity_id']} doesn't exists.");
        continue;
      }

      if (!empty($item['created'])) {
        $item['created'] = $this->createDate($item['created']);
        if ($item['created'] < $entity->get('created')->value) {
          $item['created'] = \Drupal::time()->getRequestTime();
        }
      }
      else {
        $item['created'] = \Drupal::time()->getRequestTime();
      }

      $item['entity_id'] = $entity->id();
      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if ($entity->id()) {
        $this->content[$entity->id()] = $entity;
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
      'field_comment_body' => [
        [
          'value' => $this->checkMentionOrLinkByUuid($item['body']),
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
