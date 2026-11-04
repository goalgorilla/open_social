<?php

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\social_core\Traits\ExifJpegTrait;

/**
 * Tests the PhotoSwipe size the 'file_image_default' formatter sets.
 *
 * @coversDefaultClass \Drupal\social_core\Plugin\Field\FieldFormatter\FileAndImageTableFormatter
 * @group social_core
 */
class FileAndImageTableFormatterTest extends KernelTestBase {

  use ExifJpegTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'entity_test',
    'file_mdm',
    'file_mdm_exif',
    'file_mdm_font',
    'image_effects',
    'social_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('entity_test');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['system', 'field', 'file_mdm', 'file_mdm_exif', 'image_effects']);

    FieldStorageConfig::create([
      'field_name' => 'field_files',
      'entity_type' => 'entity_test',
      'type' => 'file',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_files',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => ['file_extensions' => 'jpg'],
    ])->save();
  }

  /**
   * Tests that only the PhotoSwipe size follows the EXIF orientation.
   *
   * The raw width and height feed the thumbnail's image style, which applies
   * the orientation itself; swapping them too would rotate the size twice.
   *
   * @covers ::viewElements
   */
  public function testPhotoswipeSizeFollowsExifOrientation(): void {
    // Landscape 40x30 JPEGs: one rotated by EXIF, as a phone stores a photo
    // taken upright, and one without EXIF.
    $elements = $this->buildField([
      $this->createFile('portrait.jpg', $this->createJpeg(6)),
      $this->createFile('landscape.jpg', $this->createJpeg(NULL)),
    ]);

    $this->assertSame('30x40', $elements[0]['#item']->photoswipe_size);
    $this->assertSame('40x30', $elements[1]['#item']->photoswipe_size);
    foreach ([0, 1] as $delta) {
      $item = $elements[$delta]['#item'];
      $this->assertSame('40x30', $item->width . 'x' . $item->height);
    }
  }

  /**
   * Renders the field holding the files with the 'file_image_default' type.
   *
   * @param \Drupal\file\Entity\File[] $files
   *   The files to render.
   *
   * @return array
   *   The field render array.
   */
  private function buildField(array $files): array {
    $entity = $this->createEntity($files);

    return $this->createDisplay()->build($entity)['field_files'];
  }

  /**
   * Saves a test entity referencing the files.
   *
   * @param \Drupal\file\Entity\File[] $files
   *   The files to reference.
   *
   * @return \Drupal\entity_test\Entity\EntityTest
   *   The saved entity.
   */
  private function createEntity(array $files): EntityTest {
    $values = [];
    foreach ($files as $file) {
      $values[] = ['target_id' => $file->id(), 'display' => 1];
    }
    $entity = EntityTest::create(['field_files' => $values]);
    $entity->save();

    return $entity;
  }

  /**
   * Creates the display using the 'file_image_default' type on the field.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The display.
   */
  private function createDisplay(): EntityViewDisplay {
    return EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_files', [
      'type' => 'file_image_default',
      'settings' => ['image_style' => ''],
    ]);
  }

}
