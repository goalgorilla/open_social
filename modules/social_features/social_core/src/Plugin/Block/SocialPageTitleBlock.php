<?php

namespace Drupal\social_core\Plugin\Block;

use Drupal\Core\Block\Plugin\Block\PageTitleBlock;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a 'SocialPageTitleBlock' block.
 *
 * @Block(
 *   id = "social_page_title_block",
 *   admin_label = @Translation("Page title block"),
 * )
 */
class SocialPageTitleBlock extends PageTitleBlock implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The route match.
   *
   * @var \Drupal\social_tagging\SocialTaggingService
   */
  protected $requestStack;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The title resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SocialPageTitleBlock constructor.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    RequestStack $request_stack,
    EntityRepositoryInterface $entity_repository,
    TitleResolverInterface $title_resolver,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->entityRepository = $entity_repository;
    $this->titleResolver = $title_resolver;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('entity.repository'),
      $container->get('title_resolver'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Take the raw parameter. We'll load it ourselves.
    $nid = $this->routeMatch->getRawParameter('node');
    $node = FALSE;

    // At this point the parameter could also be a simple string of a nid.
    // EG: on: /node/%node/enrollments.
    if (!is_null($nid) && !is_object($nid)) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
    }

    $request = $this->requestStack->getCurrentRequest();

    if ($node instanceof NodeInterface) {
      // Landing pages have their own heroes. Usually we're not displayed for
      // landing page. However, when a landing page is used as a 404 or 403 page
      // then this block is still rendered. Therefor if we're asked to render
      // for a landing page we check if we're not in a 404 or 403. If we are
      // then we can quickly determine we won't render anything.
      if ($node->getType() === 'landing_page') {
        $exception = $request->attributes->get('exception');

        if (
          $exception instanceof NotFoundHttpException ||
          $exception instanceof AccessDeniedHttpException
        ) {
          return [];
        }
      }

      $route_names = $this->moduleHandler->invokeAll('social_core_node_default_title_route');

      if (!in_array($this->routeMatch->getRouteName(), array_merge([
        'entity.node.edit_form',
        'entity.node.delete_form',
        'entity.node.add_form',
      ], $route_names))) {
        $translation = $this->entityRepository->getTranslationFromContext($node);

        if ($translation instanceof NodeInterface) {
          $node->setTitle($translation->getTitle());
        }

        return [
          '#theme' => 'page_hero_data',
          '#title' => $node->getTitle(),
          '#node' => $node,
          '#hero_node' => $this->entityTypeManager->getViewBuilder('node')
            ->view($node, 'hero'),
        ];
      }
    }
    else {
      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $this->setTitle($this->titleResolver->getTitle($request, $route));
      }
      else {
        $this->setTitle('');
      }
    }

    return parent::build();
  }

}
