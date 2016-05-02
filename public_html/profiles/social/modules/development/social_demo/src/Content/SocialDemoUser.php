<?php

/**
 * @file
 * Contains \Drupal\social_demo\SocialDemoUser.
 */

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content User.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\Entity\File;
use Drupal\profile\Entity\ProfileType;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SocialDemoUser implements ContainerInjectionInterface {

  private $accounts;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface;
   */
  protected $entityStorage;

  public function __construct(UserStorageInterface $user_storage, EntityStorageInterface $entity_storage) {
    $this->userStorage = $user_storage;
    $this->entityStorage = $entity_storage;

    $yml_data = new SocialDemoParser();
    $this->accounts = $yml_data->parseFile('entity/user.yml');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('profile')
    );
  }

  /*
   * Function to create content.
   */
  public function createContent() {
    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach($this->accounts as $uuid => $account) {

      // Must have uuid and same key value.
      if ($uuid !== $account['uuid']) {
        continue;
      }

      // Check if the accounts does not exist yet
      $user_accounts = $this->userStorage->loadByProperties(array('uuid' => $uuid));
      $user_account = reset($user_accounts);

      if ($user_account) {
        echo "Account with uuid: " . $uuid . " already exists.\r\n";
        continue;
      }

      // Try and fetch the image.
      $fileClass = new SocialDemoFile();
      $fid = $fileClass->loadByUuid($account['picture']);

      $media_id = '';
      if ($file = File::load($fid)) {
        $media_id = $file->id();
      }

      // Let's create some accounts.
      $user = User::create([
        'uuid' => $account['uuid'],
        'name' => $account['name'],
        'mail' => $account['mail'],
        'init' => $account['mail'],
        'timezone' => $account['timezone'],
        'status' => $account['status'],
        'created' => REQUEST_TIME,
        'changed' => REQUEST_TIME,
      ]);
      $user->setPassword($account['name']);
      $user->enforceIsNew();
      // Save.
      $user->save();

      // Load the profile, since it's autocreated.
      $profile = $this->entityStorage->loadByProperties(array('uid' => $user->id(), 'type' => ProfileType::load('profile')->id()));
      $profile = array_pop($profile);

      // Set the field values.
      $profile->field_profile_image = $media_id;
      $profile->uuid = $account['uuid'];
      $profile->field_profile_first_name = $account['first_name'];
      $profile->field_profile_last_name = $account['last_name'];
      $profile->field_profile_organization = $account['organization'];
      $profile->field_profile_function = $account['function'];
      $profile->field_profile_phone_number = $account['phone_number'];
      $profile->field_profile_self_introduction = $account['self_introduction'];
      $profile->field_profile_expertise = $account['expertise'];
      $profile->field_profile_interests = $account['interests'];
      $profile->field_profile_address = $account['address'];

      // Save the profile.
      $profile->save();

      $content_counter++;
    }
    return $content_counter;
  }

  /*
   * Function to remove content.
   */
  public function removeContent() {
    // Loop through the content and try to create new entries.
    foreach($this->accounts as $uuid => $account) {

      // Must have uuid and same key value.
      if ($uuid !== $account['uuid']) {
        continue;
      }

      // Load the nodes from the uuid.
      $user_accounts = $this->userStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the nodes.
      foreach($user_accounts as $key => $user_account) {
        // And delete them.
        $user_account->delete();
      }
    }
  }

  public function loadUserFromUuid($uuid) {
    $user_accounts = $this->userStorage->loadByProperties(array('uuid' => $uuid));

    $user_account = reset($user_accounts);
    if ($user_account) {
      return $user_account->id();
    }
  }
}
