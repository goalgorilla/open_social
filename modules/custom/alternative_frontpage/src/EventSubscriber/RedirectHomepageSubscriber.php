<?php

namespace Drupal\alternative_frontpage\EventSubscriber;

use Drupal\alternative_frontpage\Form\AlternativeFrontpageSettings;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\State;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The RedirectHomepageSubscriber class.
 */
class RedirectHomepageSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Config Factory.
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
   * The state.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for the RedirectHomepageSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\State\State $state
   *   The state.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(
    ConfigFactory $config_factory,
    AccountProxy $current_user,
    State $state,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // 280 priority is higher than the dynamic and static page cache.
    $events[KernelEvents::REQUEST][] = ['checkForHomepageRedirect', '280'];
    return $events;
  }

  /**
   * This method is called whenever the request event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkForHomepageRedirect(GetResponseEvent $event): void {
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

    // No redirection is required for administrators. Ideally, we want to
    // check the permission (for example, "administer site configuration"),
    // but SM also has this permission, and we have no other permission to
    // check if the user is an administrator.
    if (in_array('administrator', $this->currentUser->getRoles(), TRUE)) {
      return;
    }

    $request = $event->getRequest();
    $request_path = $request->getPathInfo();
    $site_settings_frontpage = $this->configFactory->get('system.site')->get('page.front');

    $current_user_roles = $this->currentUser->getRoles(FALSE);
    $user_front_pages = $this->getAlternativePagesForUserRoles($current_user_roles);

    // We only proceed if the user requests the home page.
    if ($request_path === $site_settings_frontpage || $request_path === '/') {
      // Do nothing if the current user does not have a custom front page.
      if (empty($user_front_pages)) {
        return;
      }

      // This means that there is only one page configured for all current
      // user roles, and we can redirect them to this front page.
      if (count($user_front_pages) === 1) {
        $user_page = reset($user_front_pages);

        // We do not redirect if the user is already on the desired page,
        // otherwise there will be an endless loop.
        if ($request_path === $user_page) {
          return;
        }

        // The custom user page is different from the home page from
        // the site settings.
        if ($user_page !== $site_settings_frontpage) {
          foreach ($this->currentUser->getRoles(FALSE) as $role) {
            $cacheContext[] = 'user.roles:' . $role;
          }
          $event->setResponse($this->createRedirectResponse($cacheContext ?? [], $user_page));
        }
      }
      // This means that the user has several roles and, accordingly, the
      // front pages are set for these several roles, in this case we check
      // the role weight and use a role with a higher weight.
      else {
        $current_user_role_weight = [];

        foreach ($current_user_roles as $current_user_role) {
          /** @var \Drupal\user\RoleInterface $role */
          $role = $this->entityTypeManager->getStorage('user_role')->load($current_user_role);
          $current_user_role_weight[$current_user_role] = $role->getWeight();
        }

        $role_id_with_higher_weight = (string) array_search(max($current_user_role_weight), $current_user_role_weight, TRUE);
        $user_front_pages = $this->getAlternativePagesForUserRoles([$role_id_with_higher_weight]);
        $cacheContext = ['user.roles:' . $role_id_with_higher_weight];

        $event->setResponse($this->createRedirectResponse($cacheContext, (string) reset($user_front_pages)));
      }
    }
  }

  /**
   * Gets all custom front pages for list of roles.
   *
   * @param string[] $current_user_roles
   *   The array of user roles.
   *
   * @return string[]
   *   The array of relative URLs.
   */
  private function getAlternativePagesForUserRoles(array $current_user_roles): array {
    foreach ($current_user_roles as $role) {
      if (!empty($page = $this->getAllAlternativePages()[AlternativeFrontpageSettings::FORM_PREFIX . $role])) {
        $pages[] = $page;
      }
    }

    return $pages ?? [];
  }

  /**
   * Gets all pages from settings.
   *
   * @return array
   *   The array of custom pages from configuration.
   */
  private function getAllAlternativePages(): array {
    return $this->configFactory->get(AlternativeFrontpageSettings::CONFIG_NAME)->get('pages') ?? [];
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
  public function createRedirectResponse(array $cacheContext, string $url): CacheableRedirectResponse {
    $cache_contexts = new CacheableMetadata();
    $cache_contexts->setCacheContexts($cacheContext);

    $response = new CacheableRedirectResponse($url);
    $response->addCacheableDependency($cache_contexts);

    return $response;
  }

}
