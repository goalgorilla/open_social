<?php

namespace Drupal\social_demo;

use Drupal\profile\ProfileStorageInterface;
use Drupal\file\FileStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileType;
use Drush\Log\LogLevel;

abstract class DemoUser extends DemoContent {

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * DemoUser constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\social_demo\DemoContentParserInterface $parser
   * @param \Drupal\profile\ProfileStorageInterface $profile_storage
   * @param \Drupal\file\FileStorageInterface $file_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, ProfileStorageInterface $profile_storage, FileStorageInterface $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
    $this->profileStorage = $profile_storage;
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
      $container->get('entity.manager')->getStorage('profile'),
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
        drush_log(dt("User with uuid: {$uuid} has a different uuid in content."), LogLevel::ERROR);
        continue;
      }

      // Check whether user with same uuid already exists.
      $accounts = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($accounts) {
        drush_log(dt("User with uuid: {$uuid} already exists."), LogLevel::WARNING);
        continue;
      }

      // Load image by uuid and set to a profile.
      if (!empty($item['picture'])) {
        $item['picture'] = $this->preparePicture($item['picture']);
      }
      else {
        // Set "null" to exclude errors during saving (in cases when picture will equal to "false").
        $item['picture'] = NULL;
      }

      if (!empty($item['roles'])) {
        $item['roles'] = array_filter($item['roles']);
      }

      if (empty($item['roles'])) {
        $item['roles'] = [AccountInterface::AUTHENTICATED_ROLE];
      }

      $entry = $this->getEntry($item);
      $account = $this->entityStorage->create($entry);
      $account->setPassword($item['name']);
      $account->enforceIsNew();
      $account->save();

      if (!$account->id()) {
        continue;
      }

      $this->content[ $account->id() ] = $account;

      // Load the profile, since it's autocreated.
      $profiles = $this->profileStorage->loadByProperties([
        'uid' => $account->id(),
        'type' => ProfileType::load('profile')->id(),
      ]);
      $profile = array_pop($profiles);
      $this->fillProfile($profile, $item);
      $profile->save();
    }

    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry($item) {
    $entry = [
      'uuid' => $item['uuid'],
      'name' => $item['name'],
      'mail' => $item['mail'],
      'init' => $item['mail'],
      'timezone' => $item['timezone'],
      'status' => $item['status'],
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
      'roles' => array_values($item['roles']),
    ];

    return $entry;
  }

  /**
   * Prepares data about an image of a profile.
   *
   * @param $picture
   * @return array
   */
  protected function preparePicture($picture) {
    $value = NULL;
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $picture,
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
   * Fills the some fields of a profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   * @param array $item
   */
  protected function fillProfile($profile, $item) {
    $profile->field_profile_image = $item['picture'];
    $profile->field_profile_first_name = $item['first_name'];
    $profile->field_profile_last_name = $item['last_name'];
    $profile->field_profile_organization = $item['organization'];
    $profile->field_profile_function = $item['function'];
    $profile->field_profile_phone_number = $item['phone_number'];
    $profile->field_profile_self_introduction = $item['self_introduction'];
    $profile->field_profile_address = $item['address'];
  }

}
