<?php

/**
* @file
* Contains \Drupal\social_demo\SocialDemoPost.
*/

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Post.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_post\Entity\Post;

class SocialDemoPost implements ContainerInjectionInterface {

  private $posts;

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
  protected $postStorage;

  /*
   * Read file contents on construction.
   */
  public function __construct(UserStorageInterface $user_storage, EntityStorageInterface $entity_storage) {
    $this->userStorage = $user_storage;
    $this->postStorage = $entity_storage;

    $yml_data = new SocialDemoParser();
    $this->posts = $yml_data->parseFile('entity/post.yml');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('post')
    );
  }

  /*
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;
    // Loop through the content and try to create new entries.
    foreach($this->posts as $uuid => $post) {
      // Must have uuid and same key value.
      if ($uuid !== $post['uuid']) {
        var_dump('Post with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Check if the post does not exist yet
      $existing_posts = $this->postStorage->loadByProperties(array('uuid' => $uuid));
      $existing_post = reset($existing_posts);

      // If it already exists, leave it.
      if ($existing_post) {
        var_dump('Post with uuid: ' . $uuid . ' already exists.');
        continue;
      }

      // Try and fetch the user.
      $container = \Drupal::getContainer();
      $accountClass = SocialDemoUser::create($container);
      $user_id = $accountClass->loadUserFromUuid($post['user_id']);

      // Let's create some posts.
      $randtime = REQUEST_TIME - (rand(0, 365 * 24 * 60 * 60));
      $post_object = Post::create([
        'uuid' => $post['uuid'],
        'langcode' => $post['language'],
        'field_post' => [
          [
            'value' => $post['field_post'],
          ],
        ],
        'field_visibility' => [
          [
            'value' => $post['field_visibility'],
          ],
        ],
        'field_recipient_user' => [
          [
            'target_id' => $post['field_recipient_user'],
          ],
        ],
        'user_id' => $user_id,
        'created' => $randtime,
        'changed' => $randtime,
      ]);

      $post_object->save();

      $content_counter++;
    }

    return $content_counter;
  }

  /*
   * Function to remove content.
   */
  public function removeContent() {
    // Loop through the content and try to create new entries.
    foreach($this->posts as $uuid => $post) {

      // Must have uuid and same key value.
      if ($uuid !== $post['uuid']) {
        continue;
      }

      // Load the posts from the uuid.
      $posts = $this->postStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the posts.
      foreach($posts as $key => $post) {
        // And delete them.
        $post->delete();
      }
    }
  }

}
