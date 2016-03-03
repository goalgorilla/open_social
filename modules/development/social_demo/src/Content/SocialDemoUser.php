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
use Drupal\node\NodeStorageInterface;
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
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  public function __construct(UserStorageInterface $user_storage, NodeStorageInterface $node_storage) {
    $this->userStorage = $user_storage;
    $this->nodeStorage = $node_storage;
//    $jan = new StorageInter

    $yml_data = new SocialDemoParser();
    $this->accounts = $yml_data->parseFile('entity/user.yml');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('node')
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
        var_dump('Account with uuid: ' . $uuid . ' already exists.');
        continue;
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
