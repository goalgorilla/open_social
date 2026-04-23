<?php

namespace Drupal\social_embed\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\social_embed\Hooks\MapBlockView;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for map embed consent AJAX endpoint.
 */
class MapConsentController extends ControllerBase {

  /**
   * Constructs a MapConsentController.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    protected RendererInterface $renderer,
    ModuleHandlerInterface $moduleHandler,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Returns map block content after the user gives embed consent.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that replaces the map placeholder with the real map.
   */
  public function generate(Request $request): AjaxResponse {
    $plugin_id = $request->query->get('plugin_id');
    $uuid = $request->query->get('uuid');

    if (!is_string($plugin_id) || $plugin_id === ''
      || !is_string($uuid) || $uuid === '') {
      throw new NotFoundHttpException();
    }

    if (!in_array($plugin_id, MapBlockView::MAP_BLOCK_PLUGINS, TRUE)) {
      throw new AccessDeniedHttpException();
    }

    if (!$this->moduleHandler->moduleExists('social_geolocation_maps')) {
      throw new NotFoundHttpException();
    }

    // Parse plugin_id format "views_block:{view_id}-{display_id}".
    [, $views_part] = explode(':', $plugin_id, 2);
    [$view_id, $display_id] = explode('-', $views_part, 2);

    $view_entity = $this->entityTypeManager->getStorage('view')->load($view_id);
    if (!$view_entity) {
      throw new NotFoundHttpException();
    }
    $view = $view_entity->getExecutable();
    if (!$view->setDisplay($display_id)) {
      throw new NotFoundHttpException();
    }

    // Forward all extra query parameters as exposed view input so the map
    // result reflects the same filtering as the originating page.
    $exposed_input = $request->query->all();
    unset($exposed_input['plugin_id'], $exposed_input['uuid']);
    $view->setExposedInput($exposed_input);

    $build = $view->buildRenderable($display_id);
    if ($build === NULL) {
      throw new NotFoundHttpException();
    }
    $html = (string) $this->renderer->renderRoot($build);

    $response = new AjaxResponse();
    if (!empty($build['#attached'])) {
      $response->setAttachments($build['#attached']);
    }
    $response->addCommand(new ReplaceCommand('#social-map-placeholder-' . $uuid, $html));

    return $response;
  }

}
