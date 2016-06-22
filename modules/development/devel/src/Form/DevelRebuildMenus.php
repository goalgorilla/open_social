<?php

/**
 * @file
 * Contains \Drupal\devel\Form\DevelRebuildMenus.
 */

namespace Drupal\devel\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for rebuilding the routes.
 */
class DevelRebuildMenus extends ConfirmFormBase {

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a new DevelRebuildMenus object.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder service.
   */
  public function __construct(RouteBuilderInterface $route_builder) {
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_menu_rebuild';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to rebuild menus?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Rebuild menu based on hook_menu() and revert any custom changes. All menu items return to their default settings.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Rebuild');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->routeBuilder->rebuild();
    drupal_set_message($this->t('The menu router has been rebuilt.'));
    $form_state->setRedirect('<front>');
  }

}
