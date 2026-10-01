<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Routing;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Keeps GraphQL routes restricted to OAuth unless session auth is kept.
 *
 * Two halves of one job: alterRoutes writes the restriction, onConfigSave
 * rebuilds the router when the opt-out that drives it changes.
 *
 * A request with no credentials is not claimed by the cookie provider, so
 * core leaves it anonymous. The mobile app reads about, branding and
 * preferredFeatures before login; those stay available. A session cookie is
 * a credential this route no longer accepts.
 */
final class GraphqlAuthRouteSync extends RouteSubscriberBase {

  /**
   * The only authentication provider left on the endpoint when locked down.
   *
   * @var string[]
   */
  private const OAUTH_ONLY = ['oauth2'];

  /**
   * Constructs the subscriber.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Used to detect whether Real-Time Chat is installed.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Used to read the session-auth opt-out.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   Marks the router for rebuild when the opt-out changes.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    protected RouteBuilderInterface $routeBuilder,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Every GraphQL server, not only open_social_graphql: a renamed or added
    // server must not keep accepting the session cookie.
    $routes = array_filter(
      $collection->all(),
      static fn (Route $route): bool => $route->getDefault('_graphql') === TRUE,
    );
    if ($routes === []) {
      return;
    }
    if ($this->moduleHandler->moduleExists('social_chat')) {
      return;
    }
    // A site that never ran the update hook has no value yet. NULL is the
    // locked-down default, so the endpoint is hardened from the deploy on.
    if ($this->configFactory->get('social_graphql.settings')->get('allow_session_auth')) {
      return;
    }
    foreach ($routes as $route) {
      $route->setOption('_auth', self::OAUTH_ONLY);
    }
  }

  /**
   * Rebuilds the router when the session-auth setting changes or is deleted.
   *
   * Installing or uninstalling Real-Time Chat already rebuilds routes.
   * Saving this setting does not, so the checkbox, drush and config import
   * would otherwise leave the previous auth option in place.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() !== 'social_graphql.settings') {
      return;
    }
    if (!$event->isChanged('allow_session_auth')) {
      return;
    }
    $this->routeBuilder->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[ConfigEvents::SAVE] = 'onConfigSave';
    // A deleted object still carries its original data during dispatch, so
    // the same check falls back to the locked-down default.
    $events[ConfigEvents::DELETE] = 'onConfigSave';
    return $events;
  }

}
