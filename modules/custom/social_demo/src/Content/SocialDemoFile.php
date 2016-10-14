<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content File.
 */

use Drupal\file\Entity\File;
use Drupal\social_demo\Yaml\SocialDemoParser;

/**
 * Implements Demo content for Files.
 */
class SocialDemoFile {

  private $files;

  /**
   * Read file contents on construction.
   */
  public function __construct() {
    $yml_data = new SocialDemoParser();
    $this->files = $yml_data->parseFile('entity/file.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  /**
   * Function to create content.
   */
  public function createContent() {

    $content_counter = 0;

    // Loop through the content and try to create new entries.
    foreach ($this->files as $uuid => $file) {
      // Must have uuid and same key value.
      if ($uuid !== $file['uuid']) {
        var_dump('File with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Try and fetch the user.
      $container = \Drupal::getContainer();
      $accountClass = SocialDemoUser::create($container);
      $uid = $accountClass->loadUserFromUuid($file['uid']);

      // Get the path from the demo parser.
      $demoParser = new SocialDemoParser();
      $uri  = file_unmanaged_copy($demoParser->getPath('files' . DIRECTORY_SEPARATOR . $file['filename']), 'public://' . $file['filename'], FILE_EXISTS_REPLACE);

      $media = File::create([
        'uuid' => $file['uuid'],
        'langcode' => $file['langcode'],
        'uid' => $uid,
        'status' => $file['status'],
        'uri' => $uri,
      ]);
      $media->save();

      // Add coordinates for cropping images.
      $image_widget_crop_manager = \Drupal::service('image_widget_crop.manager');

      foreach ($file['crops'] as $crop_name => $data) {
        $crop_type = \Drupal::entityTypeManager()
          ->getStorage('crop_type')
          ->load($crop_name);

        $image_widget_crop_manager->applyCrop($data, [
          'file-uri' => $uri,
          'file-id' => $media->id(),
        ], $crop_type);
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
    foreach ($this->files as $uuid => $file) {

      // Must have uuid and same key value.
      if ($uuid !== $file['uuid']) {
        continue;
      }

      // Load the nodes from the uuid.
      $fid = $this->loadByUuid($uuid);
      // Load the file object.
      if ($file = File::load($fid)) {
        $file->delete();
      }
    }
  }

  /**
   * Load a file object by uuid.
   *
   * @param string $uuid
   *   The uuid of the file.
   *
   * @return int $fid
   *   The file id for the given uuid.
   */
  public function loadByUuid($uuid) {
    $query = \Drupal::entityQuery('file');
    $query->condition('uuid', $uuid);
    $fids = $query->execute();
    // Get a single item.
    $fid = reset($fids);
    // And return it.
    return $fid;
  }

}
