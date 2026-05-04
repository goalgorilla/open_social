<?php

namespace Drupal\social_user\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for role identification.
 */
class RoleIdentificationSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The private tempStore factory.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The current route match.
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * The session configuration.
   */
  protected SessionConfigurationInterface $sessionConfiguration;

  /**
   * Constructs a RoleIdentificationSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private tempStore factory.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   Session options from parameters (must match the PHP session cookie).
   */
  public function __construct(
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    PrivateTempStoreFactory $temp_store_factory,
    CurrentRouteMatch $current_route_match,
    SessionConfigurationInterface $session_configuration,
  ) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->tempStoreFactory = $temp_store_factory;
    $this->currentRouteMatch = $current_route_match;
    $this->sessionConfiguration = $session_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse', 0],
    ];
  }

  /**
   * Handles cookie operations on response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function onKernelResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $response = $event->getResponse();

    // Handle logout by checking the current route.
    if ($this->currentRouteMatch->getRouteName() === 'user.logout') {
      $response->headers->setCookie(
        new Cookie(
          'rid',
          '',
          time() - 3600,
          '/',
          NULL,
          TRUE,
          TRUE,
          FALSE,
          'lax'
        )
      );

      return;
    }

    $tempStore = $this->tempStoreFactory->get('social_user');

    // Handle login.
    if ($tempStore->get('role_identification_login')) {
      // Get the tracked roles from configuration.
      $tracked_roles = $this->configFactory->get('social_user.settings')->get('tracked_roles') ?? [];

      if (!empty($tracked_roles)) {
        // Find roles that are both in user's roles and tracked roles.
        $applicable_roles = array_intersect($this->currentUser->getRoles(), $tracked_roles);

        // Skip cookie setting during batch processing to avoid session
        // regeneration that causes CSRF token mismatches.
        if (!empty($applicable_roles) && !$this->isBatchPage($event->getRequest())) {
          // Create the cookie value with roles prefixed with 'cms' and base64
          // encoded individually.
          $role_values = array_map(static function ($role) {
            return base64_encode('cms' . $role);
          }, $applicable_roles);

          // Join the encoded role values with semicolons.
          $cookie_value = implode(';', $role_values);

          // Set the cookie.
          $response->headers->setCookie(
            new Cookie(
              'rid',
              $cookie_value,
              $this->getRidCookieExpire($event->getRequest()),
              '/',
              NULL,
              TRUE,
              TRUE,
              FALSE,
              'lax'
            )
          );
        }
      }

      // Clean up tempStore.
      $tempStore->delete('role_identification_login');
    }
  }

  /**
   * Expire time for the rid cookie, kept in sync with the session cookie.
   *
   * Uses \Drupal\Core\Session\SessionConfiguration, which reads
   * session.storage.options cookie_lifetime. That keeps rid and the PHP session
   * cookie on the same browser lifetime so they are discarded together.
   * Set the fallback to Drupal default as defined in the services file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return int
   *   Unix timestamp for Cookie expiry.
   */
  private function getRidCookieExpire(Request $request): int {
    $options = $this->sessionConfiguration->getOptions($request);
    $cookie_lifetime = (int) ($options['cookie_lifetime'] ?? 2000000);
    if ($cookie_lifetime > 0) {
      return time() + $cookie_lifetime;
    }
    return 0;
  }

  /**
   * Checks if the current request is a batch page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   TRUE if this is a batch page, FALSE otherwise.
   */
  private function isBatchPage(Request $request): bool {
    $path = $request->getPathInfo();
    $query = $request->query;

    // Check if this is a batch page by checking the path and query parameters.
    $pathIsBatch = $path === '/batch';
    $queryHasId = $query->has('id');
    $queryHasOp = $query->has('op');

    return $pathIsBatch && $queryHasId && $queryHasOp;
  }

}
