<?php

namespace Drupal\alternative_frontpage\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Path\PathMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\user\UserData;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class RedirectHomepageSubscriber.
 */
class RedirectHomepageSubscriber implements EventSubscriberInterface {

  /**
   * Protected var UserData.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * Protected var alternativeFrontpageSettings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $alternativeFrontpageSettings;

  /**
   * Protected var siteSettings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $siteSettings;

  /**
   * Protected var for the current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Protected var for the path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * Constructor for the RedirectHomepageSubscriber.
   */
  public function __construct(UserData $user_data, ConfigFactory $config_factory, AccountProxy $current_user, PathMatcher $path_matcher) {
    // We needs it.
    $this->userData = $user_data;
    $this->alternativeFrontpageSettings = $config_factory->get('alternative_frontpage.settings');
    $this->siteSettings = $config_factory->get('system.site');
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // 280 priority is higher than the dynamic and static page cache.
    $events[KernelEvents::REQUEST][] = ['checkForHomepageRedirect', '280'];
    return $events;
  }

  /**
   * This method is called whenever the request event is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Triggering event.
   */
  public function checkForHomepageRedirect(Event $event) {

    // Make sure front page module is not run when using cli or doing install.
    if (PHP_SAPI === 'cli' || drupal_installation_attempted()) {
      return;
    }
    // Don't run when site is in maintenance mode.
    if (\Drupal::state()->get('system.maintenance_mode')) {
      return;
    }

    // Ignore non index.php requests (like cron).
    if (!empty($_SERVER['SCRIPT_FILENAME']) && realpath(DRUPAL_ROOT . '/index.php') != realpath($_SERVER['SCRIPT_FILENAME'])) {
      return;
    }

    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $event->getRequest();
    $request_path = $request->getPathInfo();
    $frontpage_an = $this->siteSettings->get('page.front');
    if ($request_path === $frontpage_an || $request_path === '/') {
      $frontpage_lu = $this->alternativeFrontpageSettings->get('frontpage_for_authenticated_user');
      if ($frontpage_an === $frontpage_lu) {
        return;
      }
      if ($frontpage_lu && $this->currentUser->isAuthenticated()) {
        $cache_contexts = new CacheableMetadata();
        $cache_contexts->setCacheContexts(['user.roles:anonymous']);

        $response = new CacheableRedirectResponse($frontpage_lu);
        $response->addCacheableDependency($cache_contexts);
        $event->setResponse($response);
      }
    }
  }

}
