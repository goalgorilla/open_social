<?php

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Tests that the MediaBridge works with Media entities.
 *
 * @coversDefaultClass \Drupal\social_graphql\Plugin\GraphQL\DataProducer\MediaBridge
 * @group MediaBridge
 * @group social_graphql
 */
class MediaBridgeMediaTest extends MediaKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'graphql',
    'social_graphql',
  ];

  /**
   * The plugin manager for GraphQL data producers.
   *
   * @var \Drupal\graphql\Plugin\DataProducerPluginManager
   */
  protected $dataProducerPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test image.
    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://social_graphql-example-2.jpg');
    $this->image = File::create([
      'uri' => 'public://social_graphql-example-2.jpg',
    ]);
    $this->image->save();

    // Ensure we can access our data provider.
    $this->dataProducerPluginManager = $this->container->get('plugin.manager.graphql.data_producer');
  }

  /**
   * Tests that the MediaBridge class works for Media entities.
   */
  public function testMediaSource() {
    // Create an image media type.
    $image_media_type = $this->createMediaType('image');
    $title = $this->randomString();
    $alt = $this->randomString();
    /** @var \Drupal\media\Entity\Media $entity */
    $entity = Media::create([
      'bundle' => $image_media_type->id(),
      'title' => $title,
      'alt' => $alt,
    ]);
    $source_field_name = $entity->getSource()->getConfiguration()['source_field'];
    $entity->{$source_field_name}->target_id = $this->image->id();
    $entity->{$source_field_name}->alt = $alt;
    $entity->{$source_field_name}->title = $title;
    $entity->save();

    // Reload the entity since loading behaviour for fields is slightly
    // different from newly created entities.
    $entity = Media::load($entity->id());

    // Create an instance of our data producer.
    /** @var \Drupal\social_graphql\Plugin\GraphQL\DataProducer\MediaBridge $data_producer */
    $data_producer = $this->dataProducerPluginManager->createInstance('media_bridge');

    // The uuid of the Media entity.
    $resolved_uuid = $this->await($data_producer->resolve($entity, 'id'));
    self::assertEquals($entity->uuid(), $resolved_uuid);

    // The URL should be produced.
    $resolved_url = $this->await($data_producer->resolve($entity, 'url'));
    self::assertEquals($GLOBALS['base_url'] . '/' . $this->siteDirectory . '/files/social_graphql-example-2.jpg', $resolved_url);

    // The title should be produced.
    $resolved_title = $this->await($data_producer->resolve($entity, 'title'));
    self::assertEquals($title, $resolved_title);

    // The alt text should be produced.
    $resolved_alt = $this->await($data_producer->resolve($entity, 'alt'));
    self::assertEquals($alt, $resolved_alt);
  }

  /**
   * Turns a possible promise into a value.
   *
   * @param mixed $value
   *   A value that's possible a promise.
   *
   * @return mixed
   *   The value or the result of a the promise.
   */
  protected function await($value) {
    if ($value instanceof SyncPromise) {
      $value::runQueue();
      return $value->result;
    }

    return $value;
  }

}
