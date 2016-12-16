<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Group.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\file\Entity\File;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\Group;

/**
 * Implements Demo content for Groups.
 */
class SocialDemoGroup implements ContainerInjectionInterface {

  protected $groups;

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
   * Read file contents on construction.
   */
  public function __construct(UserStorageInterface $user_storage, EntityStorageInterface $entity_storage) {
    $this->userStorage = $user_storage;
    $this->groupStorage = $entity_storage;

    $yml_data = new SocialDemoParser();
    $this->groups = $yml_data->parseFile('entity/group.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('group')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;
    // Loop through the content and try to create new entries.
    foreach ($this->groups as $uuid => $group) {
      // Must have uuid and same key value.
      if ($uuid !== $group['uuid']) {
        echo "Group with uuid: " . $uuid . " has a different uuid in content.\r\n";
        continue;
      }

      // Check if the group does not exist yet.
      $existing_groups = $this->groupStorage->loadByProperties(array('uuid' => $uuid));
      $existing_group = reset($existing_groups);

      // If it already exists, leave it.
      if ($existing_group) {
        echo "Group with uuid: " . $uuid . " already exists.\r\n";
        continue;
      }

      // Try and fetch the user.
      $container = \Drupal::getContainer();
      $accountClass = SocialDemoUser::create($container);
      $user_id = $accountClass->loadUserFromUuid($group['uid']);

      // Try and fetch the image.
      $media_id = '';
      if (!empty($group['image'])) {
        $fileClass = new SocialDemoFile();
        $fid = $fileClass->loadByUuid($group['image']);
        if ($file = File::load($fid)) {
          $media_id = $file->id();
        }
      }

      // Calculate data.
      $grouptime = $this->createDate($group['created']);

      // Let's create some groups.
      $group_object = Group::create([
        'uuid' => $group['uuid'],
        'langcode' => $group['language'],
        'type' => $group['group_type'],
        'label' => $group['title'],
        'field_group_description' => $group['description'],
        'uid' => $user_id,
        'created' => $grouptime,
        'changed' => $grouptime,
        'field_group_image' => [
          [
            'target_id' => $media_id,
          ],
        ],
      ]);

      $group_object->save();

      // If it succeeded, also add some teammembers.
      if ($group_object instanceof Group) {
        foreach ($group['members'] as $uuid) {
          $user_id = $accountClass->loadUserFromUuid($uuid);
          if ($member = User::load($user_id)) {
            if(!$group_object->getMember($member)) {
              $group_object->addMember($member);
            }
          }
        }
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
    foreach ($this->groups as $uuid => $group) {

      // Must have uuid and same key value.
      if ($uuid !== $group['uuid']) {
        continue;
      }

      // Load the groups from the uuid.
      $groups = $this->groupStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the groups.
      foreach ($groups as $key => $group) {
        // And delete them.
        $group->delete();
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

    return strtotime($date);
  }

  /**
   * Load a Group from UUID.
   *
   * @param string $uuid
   *   The uuid of the group.
   *
   * @return int group id
   *   The group id.
   */
  public function loadGroupFromUuid($uuid) {
    $groups = $this->groupStorage->loadByProperties(array('uuid' => $uuid));

    $group = reset($groups);
    if ($group) {
      return $group->id();
    }
  }
}
