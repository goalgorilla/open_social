<?php

namespace Drupal\social_demo;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\file\FileStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileType;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Creates users.
 *
 * @package Drupal\social_demo
 */
abstract class DemoUser extends DemoContent {

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * DemoUser constructor.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    DemoContentParserInterface $parser,
    UserStorageInterface $user_storage,
    EntityStorageInterface $group_storage,
    FileStorageInterface $file_storage,
    TermStorageInterface $term_storage,
    LoggerChannelFactoryInterface $logger_channel_factory,
    ProfileStorageInterface $profile_storage) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $parser,
      $user_storage,
      $group_storage,
      $file_storage,
      $term_storage,
      $logger_channel_factory
    );
    $this->profileStorage = $profile_storage;
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
      $container->get('entity_type.manager')->getStorage('group'),
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('logger.factory'),
      $container->get('entity_type.manager')->getStorage('profile'),
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
        $this->loggerChannelFactory->get('social_demo')->error("User with uuid: {$uuid} has a different uuid in content.");
        continue;
      }

      // Check whether user with same uuid already exists.
      $accounts = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($accounts) {
        $this->loggerChannelFactory->get('social_demo')->warning("User with uuid: {$uuid} already exists.");
        continue;
      }

      // Load image by uuid and set to a profile.
      if (!empty($item['image'])) {
        $item['image'] = $this->prepareImage($item['image'], $item['image_alt']);
      }
      else {
        // Set "null" to exclude errors during saving
        // (in cases when image will equal to "false").
        $item['image'] = NULL;
      }

      if (!empty($item['expertise'])) {
        $item['expertise'] = $this->prepareTerms($item['expertise']);
      }
      if (!empty($item['interests'])) {
        $item['interests'] = $this->prepareTerms($item['interests']);
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

      $this->content[$account->id()] = $account;

      // Load the profile, since it's autocreated.
      $profiles = $this->profileStorage->loadByProperties([
        'uid' => $account->id(),
        'type' => ProfileType::load('profile')->id(),
      ]);
      $profile = array_pop($profiles);

      if ($profile instanceof ProfileInterface) {
        $this->fillProfile($profile, $item);
        $profile->save();
      }
    }

    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry(array $item) {
    $entry = [
      'uuid' => $item['uuid'],
      'name' => $item['name'],
      'mail' => $item['mail'],
      'init' => $item['mail'],
      'timezone' => $item['timezone'],
      'status' => $item['status'],
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'roles' => array_values($item['roles']),
    ];

    return $entry;
  }

  /**
   * Returns taxonomy terms for UUIDs.
   *
   * @param array $values
   *   A list of UUIDs for terms.
   *
   * @return array
   *   Returns an empty array or one filled with taxonomy terms.
   */
  protected function prepareTerms(array $values) {
    $terms = [];

    foreach ($values as $uuid) {
      $term = $this->termStorage->loadByProperties([
        'uuid' => $uuid,
      ]);
      $term = reset($term);

      if (!empty($term)) {
        $terms[] = [
          'target_id' => $term->id(),
        ];
      }
    }

    return $terms;
  }

  /**
   * Fills the some fields of a profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   Type of ProfileInterface.
   * @param array $item
   *   The profile field item.
   */
  protected function fillProfile(ProfileInterface $profile, array $item) {
    $profile->field_profile_image = $item['image'];
    $profile->field_profile_first_name = $item['first_name'];
    $profile->field_profile_last_name = $item['last_name'];
    $profile->field_profile_organization = $item['organization'];
    $profile->field_profile_function = $item['function'];
    $profile->field_profile_phone_number = $item['phone_number'];
    $profile->field_profile_self_introduction = $item['self_introduction'];
    $profile->field_profile_address = $item['address'];
    $profile->field_profile_expertise = $item['expertise'];
    $profile->field_profile_interests = $item['interests'];
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
      $item['name'] = $uuid;
      $item['first_name'] = 'First';
      $item['last_name'] = 'Last Name';
      $item['self_introduction'] = $uuid;
      $item['mail'] = $uuid . '@example.com';
      $item['created'] = '-' . random_int(1, 2 * 365) . ' day|' . random_int(0, 23) . ':' . random_int(0, 59);
      $new_data[$uuid] = $item;
    }
    return $new_data;
  }

}
