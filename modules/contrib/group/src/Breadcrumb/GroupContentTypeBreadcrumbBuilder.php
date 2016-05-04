<?php

/**
 * @file
 * Contains \Drupal\group\Breadcrumb\GroupContentTypeBreadcrumbBuilder.
 */

namespace Drupal\group\Breadcrumb;

use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class GroupContentTypeBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * @inheritdoc
   */
  public function applies(RouteMatchInterface $route_match) {
    // Only apply to paths containing a group content type.
    if ($route_match->getParameter('group_content_type') instanceof GroupContentTypeInterface) {
      return TRUE;
    }
  }

  /**
   * @inheritdoc
   */
  public function build(RouteMatchInterface $route_match) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
    $group_content_type = $route_match->getParameter('group_content_type');
    $group_type = $group_content_type->getGroupType();

    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Groups'), 'entity.group.collection'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Group types'), 'entity.group_type.collection'));
    $breadcrumb->addLink(Link::createFromRoute($group_type->label(), 'entity.group_type.edit_form', ['group_type' => $group_type->id()]));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Content'), 'entity.group_type.content_plugins', ['group_type' => $group_type->id()]));

    // Add a link to the Configure page for any non-default tab.
    if ($route_match->getRouteName() != 'entity.group_content_type.edit_form') {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Configure'), 'entity.group_content_type.edit_form', ['group_content_type' => $group_content_type->id()]));
    }

    // Breadcrumb needs to have the group type and group content type as
    // cacheable dependencies because any changes to them should be reflected.
    $breadcrumb->addCacheableDependency($group_type);
    $breadcrumb->addCacheableDependency($group_content_type);

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
