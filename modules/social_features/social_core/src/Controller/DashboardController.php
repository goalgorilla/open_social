<?php

namespace Drupal\social_core\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\system\Controller\SystemController;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contains various response method for Dashboard.
 *
 * @package Drupal\social_core\Controller
 */
class DashboardController extends SystemController {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The home page hero block id.
   *
   * @var null|int
   */
  private ?int $homePageHeroBlockId = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->configFactory = $container->get('config.factory');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function overview($link_id) {
    // Load all menu links below it.
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($link_id)->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $this->menuLinkTree->load('', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $tree_access_cacheability = new CacheableMetadata();
    $blocks = [];
    foreach ($tree as $key => $element) {

      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if ($element->access instanceof AccessResultInterface
        && !$element->access->isAllowed()) {
        continue;
      }

      $link = $element->link;
      $block['title'] = $link->getTitle();
      $block['description'] = $link->getDescription();
      $block['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $this->systemManager->getAdminBlock($link),
      ];

      if (!empty($block['content']['#content'])) {
        $blocks[$key] = $block;
      }
    }

    if ($blocks) {
      ksort($blocks);
      $build = [
        '#theme' => 'admin_page',
        '#blocks' => $blocks,
      ];
      $tree_access_cacheability->applyTo($build);
      return $build;
    }
    else {
      $build = [
        '#markup' => $this->t('You do not have any administrative items.'),
      ];
      $tree_access_cacheability->applyTo($build);
      return $build;
    }
  }

  /**
   * Redirect users to current active theme.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect user to current active theme.
   */
  public function getActiveTheme(): RedirectResponse {
    $theme = $this->themeHandler->getDefault();

    return new RedirectResponse(Url::fromUserInput('/admin/appearance/settings/' . $theme)->toString(), 302);
  }

  /**
   * Redirect users to the homepage block.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect users to the homepage block.
   */
  public function redirectCustomizedHomepage(): RedirectResponse {
    if ($this->getHomePageheroBlockid() !== NULL) {
      $url = "/block/{$this->getHomePageheroBlockid()}";
    }
    else {
      $url = "/block/add/hero_call_to_action_block";
    }
    return new RedirectResponse(Url::fromUserInput($url)->toString(), 302);
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
