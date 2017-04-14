<?php

namespace Drupal\social_demo;

use Drupal\user\UserStorageInterface;
use Drupal\file\FileStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\GroupInterface;
use Drush\Log\LogLevel;

abstract class DemoGroup extends DemoContent {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * DemoGroup constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\social_demo\DemoContentParserInterface $parser
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\file\FileStorageInterface $file_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, UserStorageInterface $user_storage, FileStorageInterface $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
    $this->userStorage = $user_storage;
    $this->fileStorage = $file_storage;
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
      $container->get('entity.manager')->getStorage('file')
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
        drush_log(dt("Group with uuid: {$uuid} has a different uuid in content."), LogLevel::ERROR);
        continue;
      }

      // Check whether group with same uuid already exists.
      $groups = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($groups) {
        drush_log(dt("Group with uuid: {$uuid} already exists."), LogLevel::WARNING);
        continue;
      }

      // Try to load a user account (author's account).
      $account = $this->loadByUuid('user', $item['uid']);

      if (!$account) {
        drush_log(dt("Account with uuid: {$item['uid']} doesn't exists."), LogLevel::ERROR);
        continue;
      }

      $account = current($accounts);

      // Create array with data of a group.
      $item['uid'] = $account->id();
      $item['created'] = $item['changed'] = $this->createDate($item['created']);

      // Load image by uuid and set to a group.
      if (!empty($item['image'])) {
        $item['image'] = $this->prepareImage($item['image']);
      }
      else {
        // Set "null" to exclude errors during saving (in cases when image will equal  to "false").
        $item['image'] = NULL;
      }

      // Attach key documents.
      if (!empty($item['files'])) {
        $item['files'] = $this->prepareFiles($item['files']);
      }
      else {
        // Set "null" to exclude errors during saving (in cases when array with files will empty).
        $item['files'] = NULL;
      }

      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if (!$entity->id()) {
        continue;
      }

      $this->content[ $entity->id() ] = $entity;

      if (!empty($item['members'])) {
        $this->addMembers($item['members'], $entity);
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
      'type' => $item['type'],
      'label' => $item['label'],
      'field_group_description' => [
        [
          'value' => $item['description'],
          'format' => 'basic_html',
        ]
      ],
      'uid' => $item['uid'],
      'created' => $item['created'],
      'changed' => $item['changed'],
      'field_group_image' => $item['image'],
      'field_group_files' => $item['files'],
    ];

    return $entry;
  }

  /**
   * Converts a date in the correct format.
   *
   * @param string $date_string
   * @return int|false
   */
  protected function createDate($date_string) {
    // Split from delimiter.
    $timestamp = explode('|', $date_string);

    $date = strtotime($timestamp[0]);
    $date = date('Y-m-d', $date) . 'T' . $timestamp[1] . ':00';

    return strtotime($date);
  }

  /**
   * Adds members to a group.
   *
   * @param array $members
   * @param \Drupal\group\Entity\GroupInterface $entity
   */
  protected function addMembers(array $members, GroupInterface $entity) {
    foreach ($members as $account_uuid) {
      $account = $this->userStorage->loadByProperties([
        'uuid' => $account_uuid,
      ]);

      if (($account = current($account)) && !$entity->getMember($account)) {
        $entity->addMember($account);
      }
    }
  }

  /**
   * Prepares data about an image of a group.
   *
   * @param $image
   * @return array
   */
  protected function prepareImage($image) {
    $value = NULL;
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $image,
    ]);

    if ($files) {
      $value = [
        [
          'target_id' => current($files)->id(),
        ],
      ];
    }

    return $value;
  }

  /**
   * Prepares an array with list of files to set as field value.
   *
   * @param $files
   * @return array
   */
  protected function prepareFiles($files) {
    $values = [];

    foreach ($files as $key => $file_uuid) {
      $file = $this->fileStorage->loadByProperties([
        'uuid' => $file_uuid,
      ]);

      if ($file) {
        $values[] = [
          'target_id' => current($file)->id(),
        ];
      }
    }

    return $values;
  }

}
