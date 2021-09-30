<?php

namespace Drupal\social_demo;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Drupal\image_widget_crop\ImageWidgetCropManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\crop\Entity\CropType;

/**
 * Abstract file for creating demo files.
 *
 * @package Drupal\social_demo
 */
abstract class DemoFile extends DemoContent {

  /**
   * The crop manager.
   *
   * @var \Drupal\image_widget_crop\ImageWidgetCropManager
   */
  protected $imageWidgetCropManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DemoFile constructor.
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
    ImageWidgetCropManager $image_widget_crop_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
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
    $this->imageWidgetCropManager = $image_widget_crop_manager;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('image_widget_crop.manager'),
      $container->get('entity_type.manager')
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
        $this->loggerChannelFactory->get('social_demo')->error("File with uuid: {$uuid} has a different uuid in content.");
        continue;
      }

      // Check whether file with same uuid already exists.
      $files = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($files) {
        $this->loggerChannelFactory->get('social_demo')->warning("File with uuid: {$uuid} already exists.");
        continue;
      }

      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      // Copy file from module.
      $item['uri'] = $file_system->copy(
        $this->parser->getPath($item['path'], $this->getModule(), $this->getProfile()),
        $item['uri'],
        FileSystemInterface::EXISTS_REPLACE
      );

      $item['uid'] = NULL;
      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if (!$entity->id()) {
        continue;
      }

      $this->content[$entity->id()] = $entity;

      if (!empty($item['crops'])) {
        $this->applyCrops($item, $entity);
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
      'langcode' => $item['langcode'],
      'uid' => $item['uid'],
      'status' => $item['status'],
      'uri' => $item['uri'],
    ];

    return $entry;
  }

  /**
   * Crops the images.
   *
   * @param array $item
   *   The array with items.
   * @param \Drupal\file\FileInterface $entity
   *   The FileInterface entity.
   */
  protected function applyCrops(array $item, FileInterface $entity) {
    // Add coordinates for cropping images.
    foreach ($item['crops'] as $crop_name => $data) {
      $crop_type = $this->entityTypeManager
        ->getStorage('crop_type')
        ->load($crop_name);

      if (!empty($crop_type) && $crop_type instanceof CropType) {
        $this->imageWidgetCropManager->applyCrop($data, [
          'file-uri' => $item['uri'],
          'file-id' => $entity->id(),
        ], $crop_type);
      }
    }
  }

}
