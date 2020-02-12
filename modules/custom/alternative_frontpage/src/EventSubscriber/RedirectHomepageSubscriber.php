<?php

namespace Drupal\alternative_frontpage\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * Protected var configFactory.
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
   * Protected var entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor for the RedirectHomepageSubscriber.
   */
  public function __construct(UserData $user_data, ConfigFactory $config_factory, AccountProxy $current_user, PathMatcher $path_matcher, EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager) {
    // We needs it.
    $this->userData = $user_data;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
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
    $active_language = $this->languageManager->getCurrentLanguage()->getId();
    $default_language = $this->languageManager->getDefaultLanguage()->getId();

    // Get the frontpage paths.
    $frontpage_an = $this->getConfigUrlPath('anonymous');
    $frontpage_lu = $this->getConfigUrlPath('authenticated');

    if ($request_path === '/' . $active_language || $request_path === '/') {
      if ($frontpage_an === $frontpage_lu) {
        return;
      }

      $redirect = FALSE;

      // Check if alternative frontpage has been set for anonymous.
      if ($frontpage_an && $this->currentUser->isAnonymous()) {
        $redirect_path = $frontpage_an;
        // If the active language is not the source, add the prefix.
        if ($active_language !== $default_language) {
          $redirect_path = '/' . $active_language . $frontpage_an;
        }
        $redirect = TRUE;
        $role = 'anonymous';
      }

      // Check if alternative frontpage has been set for authenticated.
      if ($frontpage_lu && $this->currentUser->isAuthenticated()) {
        $redirect_path = $frontpage_lu;
        // If the active language is not the source, add the prefix.
        if ($active_language !== $default_language) {
          $redirect_path = '/' . $active_language . $frontpage_lu;
        }
        $redirect = TRUE;
        $role = 'authenticated';
      }

      // Redirect to the correct alternative frontpage.
      if ($redirect === TRUE) {
        $cache_contexts = new CacheableMetadata();
        $cache_contexts->setCacheContexts(['user.roles:' . $role]);

        $response = new CacheableRedirectResponse($redirect_path);
        $response->addCacheableDependency($cache_contexts);
        $event->setResponse($response);
      }
    }
  }

  /**
   * Get the alternative configs based on provided role.
   */
  public function getConfigIds($role) {
    $entity = $this->entityTypeManager->getStorage('alternative_frontpage')->getQuery()
      ->condition('roles_target_id', $role)
      ->execute();
    return key($entity);
  }

  /**
   * Get the correct Url Path for the landing page.
   */
  public function getConfigUrlPath($role) {
    $active_language = $this->languageManager->getCurrentLanguage()->getId();

    // Get the frontpage for logged in users.
    $config_name = $this->getConfigIds($role);

    // Get the target language object.
    $language = $this->languageManager->getLanguage($active_language);
    // Remember original language before this operation.
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    // Set the translation target language on the configuration factory.
    $this->languageManager->setConfigOverrideLanguage($language);

    // Get the language config override.
    $path = $this->configFactory->get('alternative_frontpage.alternative_frontpage.' . $config_name);
    $path = $path->get('path');

    // Set the configuration language back.
    $this->languageManager->setConfigOverrideLanguage($original_language);

    return $path;
  }

}
