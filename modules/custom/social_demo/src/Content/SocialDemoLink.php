<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Link.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Demo content for Links.
 */
class SocialDemoLink implements ContainerInjectionInterface {

  protected $links;

  /**
   * The menu link storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * Read file contents on construction.
   */
  public function __construct(EntityStorageInterface $menu_link_storage) {
    $this->menuLinkStorage = $menu_link_storage;

    $yml_data = new SocialDemoParser();
    $this->links = $yml_data->parseFile('entity/menu-link.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('menu_link_content')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {
    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach ($this->links as $uuid => $link) {
      // Must have uuid and same key value.
      if ($uuid !== $link['uuid']) {
        var_dump('Link with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Check if the link does not exist yet.
      $links = $this->menuLinkStorage->loadByProperties(array('uuid' => $uuid));

      // If it already exists, leave it.
      if ($links) {
        var_dump('Link with uuid: ' . $uuid . ' already exists.');
        continue;
      }


      // Create entity.
      $link_content = $this->menuLinkStorage->create([
        'uuid' => $link['uuid'],
        'title' => $link['title'],
        'link' => [
          'uri' => $link['link'],
        ],
        'menu_name' => $link['menu_name'],
        'expanded' => $link['expanded'],
      ]);
      $link_content->save();
      $content_counter++;
    }

    return $content_counter;
  }

  /**
   * Function to remove content.
   */
  public function removeContent() {
    // Loop through the content and try to create new entries.
    foreach ($this->links as $uuid => $link) {

      // Must have uuid and same key value.
      if ($uuid !== $link['uuid']) {
        continue;
      }

      // Load the links from the uuid.
      $links = $this->menuLinkStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the links.
      foreach ($links as $key => $link) {
        // And delete them.
        $link->delete();
      }
    }
  }

}
