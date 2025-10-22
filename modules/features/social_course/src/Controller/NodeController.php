<?php

namespace Drupal\social_course\Controller;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\node\Controller\NodeController as NodeControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns response for Node add page.
 */
class NodeController extends NodeControllerBase {

  /**
   * The route provider to load routes by name.
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->routeProvider = $container->get('router.route_provider');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage() {
    /** @var \Drupal\social_course\Access\ContentAccessCheck $access_checker */
    $access_checker = \Drupal::service('social_course.access_checker');
    $account = \Drupal::currentUser();
    $build = [
      '#theme' => 'node_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('node_type')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use node types the user has access to.
    // @todo See how we can refactor the andIf to remove it before drupal 10.
    $route = $this->routeProvider->getRouteByName('node.add');
    foreach ($this->entityTypeManager()->getStorage('node_type')->loadMultiple() as $type) {
      $route_match = new RouteMatch('node.add', $route, ['node_type' => $type], ['node_type' => $type->id()]);
      $access = $this
        ->entityTypeManager()
        ->getAccessControlHandler('node')
        ->createAccess($type->id(), NULL, [], TRUE)
        // Access checker applies only for "node.add" route, so we need to
        // check user access to this route but not to current route match.
        ->andIf($access_checker->access($route, $route_match, $account));

      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }

      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', [
        'node_type' => $type->id(),
      ]);
    }

    $build['#content'] = $content;

    return $build;
  }

}
