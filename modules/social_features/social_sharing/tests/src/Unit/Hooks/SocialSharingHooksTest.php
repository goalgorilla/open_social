<?php

declare(strict_types=1);

namespace Drupal\Tests\social_sharing\Unit\Hooks;

use Drupal\block\BlockInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_sharing\Hooks\SocialSharingHooks;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\social_sharing\Hooks\SocialSharingHooks
 * @group social_sharing
 */
class SocialSharingHooksTest extends UnitTestCase {

  /**
   * Builds the hooks with the given value for the sharing toggle.
   */
  private function hooks(?bool $sharing_enabled): SocialSharingHooks {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('enabled')
      ->willReturn($sharing_enabled);
    $config->method('getCacheTags')
      ->willReturn(['config:social_sharing.settings']);
    $config->method('getCacheContexts')
      ->willReturn([]);
    $config->method('getCacheMaxAge')
      ->willReturn(-1);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('social_sharing.settings')
      ->willReturn($config);

    return new SocialSharingHooks($config_factory);
  }

  /**
   * Builds a block for the given plugin.
   */
  private function block(string $plugin_id): BlockInterface {
    $block = $this->createMock(BlockInterface::class);
    $block->method('getPluginId')->willReturn($plugin_id);
    return $block;
  }

  /**
   * Data provider for ::testSharingBlockAccess().
   *
   * @return array<string, array{string, bool|null, bool}>
   *   The block plugin, the sharing toggle and whether access is forbidden.
   */
  public static function sharingBlockProvider(): array {
    return [
      'shariff enabled' => ['shariff_block', TRUE, FALSE],
      'shariff disabled' => ['shariff_block', FALSE, TRUE],
      'webtools enabled' => ['social_share', TRUE, FALSE],
      'webtools disabled' => ['social_share', FALSE, TRUE],
      'not yet configured' => ['shariff_block', NULL, FALSE],
    ];
  }

  /**
   * Tests that sharing blocks follow the sharing toggle.
   *
   * @covers ::blockAccess
   * @dataProvider sharingBlockProvider
   */
  public function testSharingBlockAccess(string $plugin_id, ?bool $sharing_enabled, bool $forbidden): void {
    $result = $this->hooks($sharing_enabled)
      ->blockAccess($this->block($plugin_id), 'view', $this->createMock(AccountInterface::class));

    $this->assertSame($forbidden, $result->isForbidden());
    $this->assertInstanceOf(CacheableDependencyInterface::class, $result);
    $this->assertContains('config:social_sharing.settings', $result->getCacheTags());
  }

  /**
   * Tests that other blocks are not affected.
   *
   * @covers ::blockAccess
   */
  public function testOtherBlockAccessIsNeutral(): void {
    $result = $this->hooks(FALSE)
      ->blockAccess($this->block('system_branding_block'), 'view', $this->createMock(AccountInterface::class));

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that only viewing sharing blocks follows the sharing toggle.
   *
   * @covers ::blockAccess
   */
  public function testOtherOperationOnSharingBlockIsNeutral(): void {
    $result = $this->hooks(FALSE)
      ->blockAccess($this->block('shariff_block'), 'update', $this->createMock(AccountInterface::class));

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Tests that the sharing field is removed only when sharing is disabled.
   *
   * @covers ::entityViewAlter
   * @testWith [true, true]
   *           [false, false]
   */
  public function testSharingFieldIsRemovedWhenDisabled(bool $sharing_enabled, bool $field_kept): void {
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')
      ->with('shariff_field')
      ->willReturn(['weight' => 100]);

    $build = ['shariff_field' => ['#markup' => 'Share']];
    $this->hooks($sharing_enabled)
      ->entityViewAlter($build, $this->createMock(EntityInterface::class), $display);

    $this->assertSame($field_kept, isset($build['shariff_field']));
    $this->assertContains('config:social_sharing.settings', $build['#cache']['tags']);
  }

  /**
   * Tests that displays without the sharing field are left untouched.
   *
   * @covers ::entityViewAlter
   */
  public function testDisplayWithoutSharingFieldIsUntouched(): void {
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')->willReturn(NULL);

    $build = ['body' => ['#markup' => 'Body']];
    $this->hooks(FALSE)
      ->entityViewAlter($build, $this->createMock(EntityInterface::class), $display);

    $this->assertSame(['body' => ['#markup' => 'Body']], $build);
  }

}
