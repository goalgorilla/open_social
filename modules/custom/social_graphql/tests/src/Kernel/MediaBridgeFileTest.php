<?php

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\user\Entity\Role;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * Tests that the MediaBridge works with FileItems.
 *
 * @coversDefaultClass \Drupal\social_graphql\Plugin\GraphQL\DataProducer\MediaBridge
 * @group MediaBridge
 * @group social_graphql
 */
class MediaBridgeFileTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'image',
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
   * The image under test.
   *
   * @var \Drupal\media\Plugin\media\Source\Image
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Borrowed from \Drupal\Tests\image\Kernel\ImageItemTest.
    // Sets up an entity and file for our testing.
    $this->installEntitySchema('user');
    $this->installConfig(['user']);
    // Give anonymous users permission to access content, so they can view and
    // download public files.
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access content');
    $anonymous_role->save();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    FieldStorageConfig::create([
      'field_name' => 'image_test',
      'entity_type' => 'entity_test',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'bundle' => 'entity_test',
      'settings' => [
        'file_extensions' => 'jpg',
      ],
    ])->save();
    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://social_graphql-example.jpg');
    $this->image = File::create([
      'uri' => 'public://social_graphql-example.jpg',
    ]);
    $this->image->save();
    $this->imageFactory = $this->container->get('image.factory');

    // Ensure we can access our data provider.
    $this->dataProducerPluginManager = $this->container->get('plugin.manager.graphql.data_producer');
  }

  /**
   * Tests that the MediaBridge class works for Image fields.
   */
  public function testImageField() {
    // Create an instance of our data producer.
    /** @var \Drupal\social_graphql\Plugin\GraphQL\DataProducer\MediaBridge $data_producer */
    $data_producer = $this->dataProducerPluginManager->createInstance('media_bridge');

    // Create a test entity from core that contains an image field.
    $entity = EntityTest::create();
    $entity->image_test->target_id = $this->image->id();
    $entity->image_test->alt = $alt = $this->randomMachineName();
    $entity->image_test->title = $title = $this->randomMachineName();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Reload the entity since loading behaviour for fields is slightly
    // different from newly created entities.
    $entity = EntityTest::load($entity->id());
    $file_field = $entity->get('image_test')->first();

    // The uuid of the image.
    $resolved_uuid = $this->await($data_producer->resolve($file_field, 'id'));
    self::assertEquals($this->image->uuid(), $resolved_uuid);

    // The URL should be produced.
    $resolved_url = $this->await($data_producer->resolve($file_field, 'url'));
    self::assertEquals($GLOBALS['base_url'] . '/' . $this->siteDirectory . '/files/social_graphql-example.jpg', $resolved_url);

    // The title should be produced.
    $resolved_title = $this->await($data_producer->resolve($file_field, 'title'));
    self::assertEquals($title, $resolved_title);

    // The alt text should be produced.
    $resolved_alt = $this->await($data_producer->resolve($file_field, 'alt'));
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
