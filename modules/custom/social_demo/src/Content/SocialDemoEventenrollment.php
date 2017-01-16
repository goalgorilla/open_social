<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Eventenrollment.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Demo content for Event Enrollments.
 */
class SocialDemoEventenrollment implements ContainerInjectionInterface {

  protected $eventenrollments;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Read file contents on construction.
   */
  public function __construct(EntityStorageInterface $entity_storage, UserStorageInterface $user_storage) {
    $this->entityStorage = $entity_storage;
    $this->userStorage = $user_storage;

    $yml_data = new SocialDemoParser();
    $this->eventenrollments = $yml_data->parseFile('entity/eventenrollment.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('event_enrollment'),
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach ($this->eventenrollments as $uuid => $eventenrollment) {
      // Must have uuid and same key value.
      if ($uuid !== $eventenrollment['uuid']) {
        var_dump('Entity with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Check if the node does not exist yet.
      $entities = $this->entityStorage->loadByProperties(array('uuid' => $uuid));
      $entity = reset($entities);

      // If it already exists, leave it.
      if ($entity) {
        var_dump('Entity with uuid: ' . $uuid . ' already exists.');
        continue;
      }

      // Try and fetch the user.
      $container = \Drupal::getContainer();
      $accountClass = SocialDemoUser::create($container);
      $user_id = $accountClass->loadUserFromUuid($eventenrollment['uid']);

      // Try and fetch the Event.
      $eventClass = SocialDemoEvent::create($container);
      $event_id = $eventClass->loadByUuid($eventenrollment['field_event']);

      // Let's create some entities.
      $enrollment = EventEnrollment::create([
        'uuid' => $eventenrollment['uuid'],
        'langcode' => $eventenrollment['language'],
        'name' => substr($eventenrollment['title'], 0, 50),
        'user_id' => $user_id,
        'created' => REQUEST_TIME,
        'field_event' => $event_id,
        'field_enrollment_status' => $eventenrollment['field_enrollment_status'],
        'field_account' => $user_id,
      ]);

      $enrollment->save();

      $content_counter++;
    }

    return $content_counter;
  }

  /**
   * Function to remove content.
   */
  public function removeContent() {
    // Loop through the content and try to create new entries.
    foreach ($this->eventenrollments as $uuid => $eventenrollment) {

      // Must have uuid and same key value.
      if ($uuid !== $eventenrollment['uuid']) {
        continue;
      }

      // Load the nodes from the uuid.
      $entities = $this->entityStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the nodes.
      foreach ($entities as $key => $entity) {
        // And delete them.
        $entity->delete();
      }
    }
  }

}
