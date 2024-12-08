<?php

namespace Drupal\social_comment\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\social_comment\Form\SocialCommentAdminOverview;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new RouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    // Redirect comment/comment page to entity if applicable.
    $config = $this->configFactory->get('social_comment.comment_settings');
    $redirect_comment_to_entity = $config->get('redirect_comment_to_entity');

    /** @var \Symfony\Component\Routing\Route $route */
    $route = $collection->get('entity.comment.canonical');
    if ($redirect_comment_to_entity === TRUE && $route !== NULL) {
      $route->setDefaults([
        '_controller' => '\Drupal\social_comment\Controller\SocialCommentController::commentPermalink',
      ]);
    }

    // Override default title for comment reply page.
    // @todo For some reason this doesn't work.
    if ($route = $collection->get('comment.reply')) {
      $defaults = $route->getDefaults();
      $defaults['_title'] = t('Add new reply')->render();
      $route->setDefaults($defaults);
    }

    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('comment.admin')) {
      $defaults = $route->getDefaults();
      $defaults['_form'] = SocialCommentAdminOverview::class;
      $route->setDefaults($defaults);
    }

    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('comment.admin_approval')) {
      $defaults = $route->getDefaults();
      $defaults['_form'] = SocialCommentAdminOverview::class;
      $route->setDefaults($defaults);
    }
  }

}
