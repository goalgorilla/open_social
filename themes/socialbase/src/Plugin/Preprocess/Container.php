<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pre-processes variables for the "container" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("container")
 */
class Container extends PreprocessBase implements ContainerFactoryPluginInterface {

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    Request $request
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);

    // For pages in search we would like to render containers without divs.
    $routename = $this->request
      ->get(RouteObjectInterface::ROUTE_NAME);
    if (strpos($routename, 'search') !== FALSE) {

      // Exclude the filter block on the search page.
      if (!isset($variables['element']['#exposed_form'])) {
        $variables['bare'] = TRUE;
      }
    }

    // Remove extra wrapper for container of post image form.
    if (isset($variables['element']['#id']) && $variables['element']['#id'] == 'edit-field-comment-files-wrapper') {
      $variables['bare'] = TRUE;
    }

    if (isset($variables['element']['#inline'])) {
      $variables['bare'] = TRUE;
    }

    if (isset($variables['element']['#type']) && $variables['element']['#type'] == 'view') {
      $variables['bare'] = TRUE;
    }

    // Identify the container used for search in the nav bar.
    // Var is set in hook_preprocess_block.
    if (isset($variables['element']['#addsearchicon'])) {
      $variables['bare'] = TRUE;
    }

    // Identify the container used for views_exposed filter.
    // Var is set in hook_preprocess_views_exposed_form.
    if (isset($variables['element']['#exposed_form'])) {
      $variables['exposed_form'] = TRUE;
    }

      // When we are dealing with the administration toolbar, we should not
      // set bare to TRUE because it will remove necessary classes from the admin
      // toolbar.
      if (isset($variables['element']['administration_menu'])) {
        $variables['bare'] = FALSE;
        // For the administration menu, which is the key of the render array we
        // use in SocialAdminMenuAdministratorMenuLinkTreeManipulators.php
        // we want to remove the url.path.is_front cache context.
        if (!empty($variables['#cache']['contexts'])) {
          if (($is_front = array_search('url.path.is_front', $variables['#cache']['contexts'])) !== FALSE) {
            unset($variables['#cache']['contexts'][$is_front]);
          }
        }
      }

  }

}
