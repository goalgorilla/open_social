<?php

namespace Drupal\alternative_frontpage\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\State\State;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\user\UserData;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class RedirectHomepageSubscriber.
 */
class RedirectHomepageSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * The state.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor for the RedirectHomepageSubscriber.
   *
   * @param \Drupal\user\UserData $user_data
   *   User data.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Path\PathMatcher $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\State\State $state
   *   The state.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(UserData $user_data, ConfigFactory $config_factory, AccountProxy $current_user, PathMatcher $path_matcher, State $state, MessengerInterface $messenger) {
    // We needs it.
    $this->userData = $user_data;
    $this->alternativeFrontpageSettings = $config_factory->get('alternative_frontpage.settings');
    $this->siteSettings = $config_factory->get('system.site');
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->state = $state;
    $this->messenger = $messenger;
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
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Triggering event.
   */
  public function checkForHomepageRedirect(RequestEvent $event) {

    // Make sure front page module is not run when using cli or doing install.
    if (PHP_SAPI === 'cli' || InstallerKernel::installationAttempted()) {
      return;
    }
    // Don't run when site is in maintenance mode.
    if ($this->state->get('system.maintenance_mode')) {
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
        // Check if sitemanager, or content manager are
        // previewing the anonymous page.
        // This is needed because the redirect happens twice, so we
        // need to know if we did the redirect.
        $isPreview = $request->query->get('preview');
        if ($isPreview) {
          return;
        }

        // Don't redirect site managers,content managers so they
        // can preview the anonymous page.
        $roles = ['sitemanager', 'contentmananger'];
        if ($this->currentUser->id() == "1" || array_intersect($roles, $this->currentUser->getRoles()) && $request_path == $frontpage_an) {
          $this->messenger->addWarning($this->t(
            "This page is redirected to @url_link, but we deferred the redirect to give you an opportunity to edit the content.",
            [
              '@url_link' => $frontpage_lu,
            ]));

          $cacheContext = [
            'user.roles:sitemanager',
            'user.roles:contentmanager',
          ];

          /** @var string $frontpage_an */
          $redirectUrl = $frontpage_an . '?preview=true';
          $event->setResponse(
            $this->createRedirectResponse($cacheContext, $redirectUrl)
          );

          return;
        }

        $cacheContext = ['user.roles:anonymous'];
        /** @var string $frontpage_lu */
        $event->setResponse(
          $this->createRedirectResponse($cacheContext, $frontpage_lu)
        );
      }
    }
  }

  /**
   * Helper function to build the redirect response.
   *
   * @param array $cacheContext
   *   Array of cache context items.
   * @param string $url
   *   Url string.
   *
   * @return \Drupal\Core\Cache\CacheableRedirectResponse
   *   Redirect response.
   */
  public function createRedirectResponse(array $cacheContext, $url) {
    $cache_contexts = new CacheableMetadata();
    $cache_contexts->setCacheContexts($cacheContext);

    $response = new CacheableRedirectResponse($url);
    $response->addCacheableDependency($cache_contexts);

    return $response;
  }

}
