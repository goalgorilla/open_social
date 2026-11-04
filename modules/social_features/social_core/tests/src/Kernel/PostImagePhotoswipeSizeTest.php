<?php

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\social_core\Traits\ExifJpegTrait;

/**
 * Tests the PhotoSwipe size social_core_preprocess_field() sets on post images.
 *
 * @group social_core
 */
class PostImagePhotoswipeSizeTest extends KernelTestBase {

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

    foreach (['field_post_image', 'field_other_image'] as $field_name) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'entity_test',
        'type' => 'image',
      ])->save();
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
      ])->save();
    }
  }

  /**
   * Tests that only the PhotoSwipe size of a post image follows EXIF.
   */
  public function testPostImageGetsOrientedSize(): void {
    $build = $this->renderField('field_post_image', 'image', 6);

    $item = $build[0]['#item'];
    $this->assertSame('30x40', $item->photoswipe_size);
    $this->assertSame('40x30', $item->width . 'x' . $item->height);
  }

  /**
   * Tests that another image field gets no PhotoSwipe size.
   */
  public function testOtherFieldIsLeftAlone(): void {
    $build = $this->renderField('field_other_image', 'image', 6);

    $this->assertNull($build[0]['#item']->photoswipe_size);
  }

  /**
   * Tests that a post image formatter without an #item does not break.
   */
  public function testFormatterWithoutItemIsSkipped(): void {
    $build = $this->renderField('field_post_image', 'image_url', 6);

    $this->assertArrayNotHasKey('#item', $build[0]);
    $this->assertStringContainsString('photo.jpg', $this->getRawContent());
  }

  /**
   * Renders an image field holding a 40x30 JPEG through the theme layer.
   *
   * @param string $field_name
   *   The image field to render.
   * @param string $formatter
   *   The formatter to render it with.
   * @param int|null $orientation
   *   The EXIF Orientation tag of the JPEG.
   *
   * @return array
   *   The rendered field render array.
   */
  private function renderField(string $field_name, string $formatter, ?int $orientation): array {
    $file = $this->createFile('photo.jpg', $this->createJpeg($orientation));
    $entity = EntityTest::create([$field_name => ['target_id' => $file->id()]]);
    $entity->save();

    $build = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($field_name, [
      'type' => $formatter,
      'settings' => ['image_style' => ''],
    ])->build($entity)[$field_name];
    $this->render($build);

    return $build;
  }

}
