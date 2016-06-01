<?php

namespace Drupal\Tests\block\Unit;

use Drupal\block\BlockInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\BlockCreationTrait;

/**
 * Tests block_rebuild().
 *
 * @group block
 */
class BlockRebuildTest extends KernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['stable', 'classy']);
  }

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();

    // @todo Once block_rebuild() is refactored to auto-loadable code, remove
    //   this require statement.
    require_once static::getDrupalRoot() . '/core/modules/block/block.module';
  }

  /**
   * @covers ::block_rebuild
   */
  public function testRebuildNoBlocks() {
    block_rebuild();
    $messages = drupal_get_messages();
    $this->assertEquals([], $messages);
  }

  /**
   * @covers ::block_rebuild
   */
  public function testRebuildNoInvalidBlocks() {
    $this->placeBlock('system_powered_by_block', ['region' => 'content', 'theme' => 'classy']);
    $this->placeBlock('system_powered_by_block', ['region' => BlockInterface::BLOCK_REGION_NONE, 'theme' => 'classy']);

    block_rebuild();
    $messages = drupal_get_messages();
    $this->assertEquals([], $messages);
  }

  /**
   * @covers ::block_rebuild
   */
  public function testRebuildInvalidBlocks() {
    $this->placeBlock('system_powered_by_block', ['region' => 'content', 'theme' => 'classy']);
    $block = $this->placeBlock('system_powered_by_block', ['region' => 'INVALID', 'theme' => 'classy']);

    block_rebuild();
    $messages = drupal_get_messages();
    $expected = ['warning' => [new TranslatableMarkup('The block %info was assigned to the invalid region %region and has been disabled.', ['%info' => $block->id(), '%region' => 'INVALID'])]];
    $this->assertEquals($expected, $messages);
  }

}
