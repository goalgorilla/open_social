<?php

namespace Drupal\social_group_secret\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber;
use Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\r4032login\EventSubscriber\R4032LoginSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class SocialGroupSecretSubscriber.
 *
 * @package Drupal\social_group_secret\EventSubscriber
 */
class SocialGroupSecretSubscriber extends R4032LoginSubscriber {

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The custom page HTML exception.
   *
   * @var \Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber
   */
  protected $exceptionCustomPageHtml;

  /**
   * The default HTML exception.
   *
   * @var \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber
   */
  protected $exceptionDefaultHtml;

  /**
   * Constructs a new SocialGroupSecretSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber $exception_custom_page_html
   *   The custom page HTML exception.
   * @param \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber $exception_default_html
   *   The default HTML exception.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountInterface $current_user,
    RedirectDestinationInterface $redirect_destination,
    PathMatcherInterface $path_matcher,
    EventDispatcherInterface $event_dispatcher,
    RouteMatchInterface $route_match,
    CustomPageExceptionHtmlSubscriber $exception_custom_page_html,
    DefaultExceptionHtmlSubscriber $exception_default_html
  ) {
    parent::__construct($config_factory, $current_user, $redirect_destination, $path_matcher, $event_dispatcher);

    $this->routeMatch = $route_match;
    $this->exceptionCustomPageHtml = $exception_custom_page_html;
    $this->exceptionDefaultHtml = $exception_default_html;
  }

  /**
   * {@inheritdoc}
   */
  public function on403(GetResponseEvent $event) {
    $group = $this->routeMatch->getParameter('group');

    // Show 404 page instead of 403 page for secret groups.
    if ($group && $group instanceof GroupInterface && $group->bundle() === 'secret_group') {
      $config = $this->configFactory->get('system.site');

      if ($config->get('page.404')) {
        $this->exceptionCustomPageHtml->on404($event);
      }
      else {
        $this->exceptionDefaultHtml->on404($event);
      }
    }
    else {
      parent::on403($event);
    }
  }

}
