<?php

namespace Drupal\social_geolocation;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class to define the search breadcrumb builder.
 */
class SocialGeolocationBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $search_routes = array(
      'view.search_content_proximity.page',
      'view.search_content_proximity.page_no_value',
      'view.search_users_proximity.page',
      'view.search_users_proximity.page_no_value',
      'view.search_groups_proximity.page',
      'view.search_groups_proximity.page_no_value',
    );
    return in_array($route_match->getRouteName(), $search_routes);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    switch ($route_match->getRouteName()) {
      case 'view.search_content_proximity.page':
      case 'view.search_content_proximity.page_no_value':
        $page_title = $this->t('Search content');
        break;

      case 'view.search_users_proximity.page':
      case 'view.search_users_proximity.page_no_value':
        $page_title = $this->t('Search users');
        break;

      case 'view.search_groups_proximity.page':
      case 'view.search_groups_proximity.page_no_value':
        $page_title = $this->t('Search groups');
        break;

      default:
        $page_title = $this->t('Search');
    }

    $breadcrumb->addLink(Link::createFromRoute($page_title, '<none>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
