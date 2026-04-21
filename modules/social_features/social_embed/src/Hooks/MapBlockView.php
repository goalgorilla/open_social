<?php

namespace Drupal\social_embed\Hooks;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\hux\Attribute\Alter;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gates map blocks behind embed consent settings.
 */
class MapBlockView implements ContainerInjectionInterface {

  /**
   * Twig template for the map consent placeholder.
   */
  public const CONSENT_PLACEHOLDER_TEMPLATE = '<div class="social-embed-container geolocation-map--placeholder" id="social-map-placeholder-{{ uuid }}">
      <div class="social-embedded-btn">
        <svg class="badge__icon"><use xlink:href="#icon-visibility_off"></use></svg>
        <p class="social-embed-placeholder-body">{% trans %}By clicking show map, you agree to load the embedded map from <b>OpenStreetMap</b> and therefore its privacy policy.{% endtrans %}</p>
        <div><a class="use-ajax btn btn-primary waves-effect waves-btn social-embed-placeholder-btn" href="{{ map_consent_url }}">{% trans %}Show map{% endtrans %}</a></div>
      </div>
    </div>';

  /**
   * Map block plugin IDs that require embed consent gating.
   */
  public const MAP_BLOCK_PLUGINS = [
    'views_block:social_geolocation_members-members_map_block',
    'views_block:social_geolocation_groups-groups_map_block',
    'views_block:social_geolocation_leaflet_commonmap_with_marker_icons-block_community_events_map',
    'views_block:social_geolocation_leaflet_commonmap_with_marker_icons-block_upcomming_events_map',
  ];

  /**
   * Constructs a MapBlockView.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    protected AccountProxyInterface $currentUser,
    protected UuidInterface $uuid,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('uuid'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Implements hook_block_view_alter().
   */
  #[Alter('block_view')]
  public function blockViewAlter(array &$build, BlockPluginInterface $block): void {
    // When social_cookie is enabled it handles map consent via cookie-based
    // consent instead of embed settings.
    if ($this->moduleHandler->moduleExists('social_cookie')) {
      return;
    }

    if (!$this->moduleHandler->moduleExists('social_geolocation_maps')) {
      return;
    }

    if (!in_array($block->getPluginId(), self::MAP_BLOCK_PLUGINS, TRUE)) {
      return;
    }

    if (!$this->isConsentRequired()) {
      return;
    }

    $build = $this->buildConsentPlaceholder($block);
  }

  /**
   * Checks whether embed consent is required for the current user.
   *
   * Mirrors the logic used by SocialEmbedUrlEmbedFilter for video embeds.
   */
  protected function isConsentRequired(): bool {
    $settings = $this->configFactory->get('social_embed.settings');

    if ($this->currentUser->isAnonymous()) {
      return !empty($settings->get('embed_consent_settings_an'));
    }

    if (!$settings->get('embed_consent_settings_lu')) {
      return FALSE;
    }

    // When the per-user consent field exists, respect the user's preference.
    // When the field is absent or has no value, default to requiring consent
    // since the admin setting is enabled.
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if ($user instanceof UserInterface
      && $user->hasField('field_user_embed_content_consent')) {
      $value = $user->get('field_user_embed_content_consent')->getValue();
      return empty($value) || !empty($value[0]['value']);
    }

    return TRUE;
  }

  /**
   * Returns the route name for the map consent AJAX endpoint.
   */
  protected function getConsentRoute(): string {
    return 'social_geolocation_maps.map_consent';
  }

  /**
   * Returns cache metadata for the consent placeholder.
   *
   * @return array<string, mixed>
   *   A #cache render array.
   */
  protected function getConsentCacheMetadata(): array {
    return [
      'contexts' => ['user'],
      'tags' => ['config:social_embed.settings'],
    ];
  }

  /**
   * Builds the consent placeholder render array for a map block.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin being viewed.
   *
   * @return array<string, mixed>
   *   A render array containing the consent placeholder.
   */
  protected function buildConsentPlaceholder(BlockPluginInterface $block): array {
    $uuid = $this->uuid->generate();

    return [
      '#type' => 'inline_template',
      '#template' => self::CONSENT_PLACEHOLDER_TEMPLATE,
      '#context' => [
        'uuid' => $uuid,
        'map_consent_url' => Url::fromRoute($this->getConsentRoute(), [], [
          'query' => [
            'plugin_id' => $block->getPluginId(),
            'uuid' => $uuid,
          ],
        ])->toString(),
      ],
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
          'social_embed/consent-placeholder',
        ],
      ],
      '#cache' => $this->getConsentCacheMetadata(),
    ];
  }

}
