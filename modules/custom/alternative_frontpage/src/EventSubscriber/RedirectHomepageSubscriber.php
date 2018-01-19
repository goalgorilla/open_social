<?php

namespace Drupal\alternative_frontpage\EventSubscriber;

use Drupal\Core\Path\PathMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
   * Protected var ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

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
    $this->configFactory = $config_factory->get('alternative_frontpage.settings');
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // 280 priority is higher than the dynamic and static page cache.
    $events[KernelEvents::REQUEST][] = ['checkForHomepageRedirect'];
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

    $isFrontPage = $this->pathMatcher->isFrontPage();
    if ($isFrontPage) {
      // Get the current user.
      $front_page = $this->configFactory->get('frontpage_for_authenticated_user');
      if ($front_page && $this->currentUser->isAuthenticated()) {
        $event->setResponse(new RedirectResponse($front_page));
      }
    }
  }

}
