<?php

namespace Drupal\social_comment\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
  public function alterRoutes(RouteCollection $collection) {
    // Redirect comment/comment page to entity if applicable.
    $config = $this->configFactory->get('social_comment.comment_settings');
    $redirect_comment_to_entity = $config->get('redirect_comment_to_entity');

    /** @var \Symfony\Component\Routing\Route $route */
    if ($redirect_comment_to_entity === TRUE && $route = $collection->get('entity.comment.canonical')) {
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
      $defaults['_form'] = '\Drupal\social_comment\Form\SocialCommentAdminOverview';
      $route->setDefaults($defaults);
    }

    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('comment.admin_approval')) {
      $defaults = $route->getDefaults();
      $defaults['_form'] = '\Drupal\social_comment\Form\SocialCommentAdminOverview';
      $route->setDefaults($defaults);
    }
  }

}
