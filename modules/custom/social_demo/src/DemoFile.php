<?php

namespace Drupal\social_demo;

use Drupal\image_widget_crop\ImageWidgetCropManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drush\Log\LogLevel;
use Drupal\crop\Entity\CropType;

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
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\social_demo\DemoContentParserInterface $parser
   * @param \Drupal\image_widget_crop\ImageWidgetCropManager $image_widget_crop_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DemoContentParserInterface $parser, ImageWidgetCropManager $image_widget_crop_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->parser = $parser;
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
        drush_log(dt("File with uuid: {$uuid} has a different uuid in content."), LogLevel::ERROR);
        continue;
      }

      // Check whether file with same uuid already exists.
      $files = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      if ($files) {
        drush_log(dt("File with uuid: {$uuid} already exists."), LogLevel::WARNING);
        continue;
      }

      // Copy file from module.
      $item['uri'] = file_unmanaged_copy(
        $this->parser->getPath($item['path'], $this->getModule()),
        $item['uri'],
        FILE_EXISTS_REPLACE
      );

      $item['uid'] = NULL;
      $entry = $this->getEntry($item);
      $entity = $this->entityStorage->create($entry);
      $entity->save();

      if (!$entity->id()) {
        continue;
      }

      $this->content[ $entity->id() ] = $entity;

      if (!empty($item['crops'])) {
        $this->applyCrops($item, $entity);
      }
    }

    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntry($item) {
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
   * @param \Drupal\file\FileInterface $entity
   */
  protected function applyCrops($item, $entity) {
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
