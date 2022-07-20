<?php

namespace Drupal\social_core\Plugin\Menu;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Menu link for the homepage hero block.
 */
class HomepageConfigurationHeroBlockLink extends MenuLinkDefault {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The home page hero block id.
   *
   * @var null|int
   */
  private ?int $homePageHeroBlockId = NULL;

  /**
   * Constructs a new SignupMenuLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StaticMenuLinkOverridesInterface $static_override,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);

    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;

    if ($this->getHomePageheroBlockid() !== NULL) {
      $this->pluginDefinition['url'] = "internal:/block/{$this->getHomePageheroBlockid()}";
      $this->pluginDefinition['title'] = $this->t('Customize home page');
      $this->pluginDefinition['description'] = $this->t('Change the image and text on the home page.');
    }
    else {
      $this->pluginDefinition['url'] = "internal:/block/add/hero_call_to_action_block";
      $this->pluginDefinition['title'] = $this->t('Customize home page hero header block');
      $this->pluginDefinition['description'] = $this->t('Add a "Hero call to action block" and set it to hero region to customize front page.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Get the homepage block.
   *
   * @return null|int
   *   The homepage block id or null.
   */
  private function getHomePageheroBlockid(): ?int {
    // If we already have our id, just return it.
    if ($this->homePageHeroBlockId !== NULL) {
      return $this->homePageHeroBlockId;
    }

    // Get the hero block homepage settings.
    $hero_block_settings = $this->getHeroBlockSettings();
    if ($hero_block_settings !== NULL) {
      $this->setHomePageHeroBlockId($hero_block_settings['block_content_id']);
    }

    return $this->homePageHeroBlockId;
  }

  /**
   * Set the homepage hero block id.
   *
   * @param int $homePageHeroBlockId
   *   The homepage hero block id.
   */
  public function setHomePageHeroBlockId(int $homePageHeroBlockId): void {
    $this->homePageHeroBlockId = $homePageHeroBlockId;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $hero_block_settings = $this->getHeroBlockSettings();
    if ($hero_block_settings !== NULL) {
      return ["config:block.block.{$hero_block_settings['block_id']}"];
    }

    return ['config:block.block.*'];
  }

  /**
   * Return the necessary homepage block information if it exists.
   *
   * @return null|array
   *   The configuration for the homepage block or null.
   */
  private function getHeroBlockSettings(): ?array {
    // Get a list of all hero_call_to_action_block blocks.
    $block_content = $this->entityTypeManager
      ->getStorage('block_content')
      ->loadByProperties(['type' => 'hero_call_to_action_block']);

    $home_page = $this->configFactory->get('system.site')->get('page.front');

    foreach ($block_content as $block_id => $hero_block) {
      // Load block settings for each of the hero_call_to_action_block blocks.
      $block_settings = $this->entityTypeManager
        ->getStorage('block')
        ->loadByProperties(
          [
            'region' => 'hero',
            'theme' => $this->configFactory->get('system.theme')->get('default'),
            'plugin' => "block_content:{$hero_block->uuid()}",
          ]
        );

      /** @var \Drupal\block\Entity\Block $block_settings */
      $block_settings = reset($block_settings);

      if (
        $block_settings instanceof Block &&
        strpos($block_settings->getVisibility()['request_path']['pages'], $home_page) !== FALSE &&
        array_key_exists(RoleInterface::ANONYMOUS_ID, $block_settings->getVisibility()['user_role']['roles'])
      ) {
        return [
          'block_content_id' => $block_id,
          'block_id' => $block_settings->id(),
        ];
      }
    }

    return NULL;
  }

}
