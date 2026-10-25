<?php

declare(strict_types=1);

namespace Drupal\social_sharing\Hooks;

use Drupal\block\BlockInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;

/**
 * Removes the sharing functionality when a site manager disabled it.
 *
 * Sharing is provided either by Shariff or by the OpenEuropa
 * Webtools Social Share (social_oe_webtools), which also adds the sharing
 * actions when selecting text. Both render as a block or as the
 * "shariff_field" extra field on entity displays.
 */
final readonly class SocialSharingHooks {

  /**
   * Block plugins that render sharing buttons.
   */
  private const array SHARING_BLOCK_PLUGINS = [
    'shariff_block',
    'social_share',
  ];

  /**
   * The extra field that renders sharing buttons on entity displays.
   */
  private const string SHARING_FIELD = 'shariff_field';

  public function __construct(
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Denies access to sharing blocks when sharing is disabled.
   */
  #[Hook('block_access')]
  public function blockAccess(BlockInterface $block, string $operation, AccountInterface $account): AccessResultInterface {
    if ($operation !== 'view' || !in_array($block->getPluginId(), self::SHARING_BLOCK_PLUGINS, TRUE)) {
      return AccessResult::neutral();
    }

    $config = $this->getSettings();

    return AccessResult::forbiddenIf(!$this->isSharingEnabled($config))
      ->addCacheableDependency($config);
  }

  /**
   * Removes the sharing buttons from entities when sharing is disabled.
   */
  #[Alter('entity_view')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    if (!$display->getComponent(self::SHARING_FIELD)) {
      return;
    }

    $config = $this->getSettings();
    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($config)
      ->applyTo($build);

    if (!$this->isSharingEnabled($config)) {
      unset($build[self::SHARING_FIELD]);
    }
  }

  /**
   * Returns the settings that contain the sharing toggle.
   */
  private function getSettings(): ImmutableConfig {
    return $this->configFactory->get('social_sharing.settings');
  }

  /**
   * Checks whether sharing is enabled; it is unless explicitly turned off.
   */
  private function isSharingEnabled(ImmutableConfig $config): bool {
    return $config->get('enabled') !== FALSE;
  }

}
