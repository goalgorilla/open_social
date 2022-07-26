<?php

namespace Drupal\social_core\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\system\Controller\SystemController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contains various response method for Dashboard.
 *
 * @package Drupal\social_core\Controller
 */
class DashboardController extends SystemController {

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

}
