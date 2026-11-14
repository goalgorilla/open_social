<?php

namespace Drupal\alternative_frontpage\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\State\StateInterface;
use Drupal\user\UserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;
use Drupal\path_alias\AliasRepositoryInterface;

/**
 * The RedirectHomepageSubscriber class.
 */
class RedirectHomepageSubscriber implements EventSubscriberInterface {

  /**
   * Protected var UserData.
   *
   * @var \Drupal\user\UserData
   */
  protected UserData $userData;

  /**
   * The config factory to perform operations on the configs.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * Protected var for the current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * Protected var for the path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected PathMatcher $pathMatcher;

  /**
   * Protected var entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The path alias repository.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected AliasRepositoryInterface $pathAliasRepository;

  /**
   * The inbound path processor manager.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected InboundPathProcessorInterface $pathProcessor;

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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\path_alias\AliasRepositoryInterface $path_alias_repository
   *   The path alias repository.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor manager.
   */
  public function __construct(
    UserData $user_data,
    ConfigFactory $config_factory,
    AccountProxy $current_user,
    PathMatcher $path_matcher,
    StateInterface $state,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    AliasRepositoryInterface $path_alias_repository,
    InboundPathProcessorInterface $path_processor,
  ) {
    // We need it.
    $this->userData = $user_data;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->pathAliasRepository = $path_alias_repository;
    $this->pathProcessor = $path_processor;
  }

  /**
   * Registers the home page redirect as kernel request event.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    // 280 priority is higher than the dynamic and static page cache.
    $events[KernelEvents::REQUEST][] = ['checkForHomepageRedirect', '280'];
    return $events;
  }

  /**
   * This method is called whenever the request event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Triggering event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkForHomepageRedirect(RequestEvent $event): void {
    // Make sure front page module is not run when using cli or doing install.
    if (PHP_SAPI === 'cli' || InstallerKernel::installationAttempted()) {
      return;
    }
    // Don't run when site is in maintenance mode.
    if ($this->state->get('system.maintenance_mode')) {
      return;
    }

    // Ignore non index.php requests (like cron).
    if (!empty($_SERVER['SCRIPT_FILENAME']) && realpath(DRUPAL_ROOT . '/index.php') !== realpath($_SERVER['SCRIPT_FILENAME'])) {
      return;
    }

    $response = $this->getHomepageRedirect($event->getRequest());
    if ($response !== NULL) {
      $event->setResponse($response);
    }
  }

  /**
   * Gets the redirect to the front page of the current user, if one is needed.
   *
   * A redirect is needed when the request is for the site's front page, in any
   * language and by any of its paths, and the current user has another front
   * page. The redirect stays in the language of the requested URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to check. It must be the current request, because the
   *   language of the URL is negotiated from the current request.
   *
   * @return \Drupal\Core\Cache\CacheableRedirectResponse|null
   *   The redirect, or NULL when the request should be handled as it is.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHomepageRedirect(Request $request): ?CacheableRedirectResponse {
    $site_settings_frontpage = $this->configFactory->get('system.site')->get('page.front');
    // Without a front page there is nothing to redirect from, and Drupal's
    // front page path processor throws a "not found" exception for "/".
    if (!is_string($site_settings_frontpage) || $site_settings_frontpage === '') {
      return NULL;
    }

    // Find out which system path is requested, the same way the router does
    // later in the request: the language prefix is removed, "/" becomes the
    // site's front page and an alias becomes the path it belongs to. This
    // subscriber runs before routing, so it has to do this itself. The request
    // is cloned because path processors may change the request they are given.
    $request_path = $this->pathProcessor->processInbound($request->getPathInfo(), clone $request);

    // The site's front page is configured as a system path on Drupal's own
    // form, but as it was typed, so possibly as an alias, when it was set by
    // saving the front page for anonymous users.
    $frontpage_path = $this->getSystemPath($site_settings_frontpage);

    // An alias only exists in its own language. When the site's front page is
    // an alias and the request is in another language, Drupal can't find the
    // page that belongs to it and would answer "not found".
    $is_frontpage_of_other_language = $request_path === $site_settings_frontpage && $request_path !== $frontpage_path;

    // We only proceed if the user requests the front page.
    if ($request_path !== $frontpage_path && !$is_frontpage_of_other_language) {
      return NULL;
    }

    // Administrators get the front page for authenticated users. Ideally, we
    // want to check the permission (for example, "administer site
    // configuration"), but SM also has this permission, and we have no other
    // permission to check if the user is an administrator.
    if (in_array('administrator', $this->currentUser->getRoles(), TRUE)) {
      $authenticated_page = $this->entityTypeManager
        ->getStorage('alternative_frontpage')
        ->loadByProperties(['roles_target_id' => AccountInterface::AUTHENTICATED_ROLE]);

      // No redirection is required for administrators when there is no
      // alternative front page for authenticated users.
      if (empty($authenticated_page)) {
        return NULL;
      }

      /** @var \Drupal\alternative_frontpage\Entity\AlternativeFrontpage $authenticated_page */
      $authenticated_page = reset($authenticated_page);
      $user_role = 'administrator';
      $user_page = (string) $authenticated_page->path;
    }
    else {
      $current_user_data = $this->getAlternativePageDataForCurrentUser($this->currentUser->getRoles(FALSE));
      $user_role = $current_user_data['role'] ?? '';
      $user_page = $current_user_data['page'] ?? '';
    }

    // Do nothing if the current user does not have a custom front page.
    if ($user_page === '') {
      return NULL;
    }

    // The front page of the user may be configured as an alias too, so we
    // compare system paths.
    $user_page = $this->getSystemPath($user_page);

    // The front page for the user is the same as in the site configuration,
    // and we are not redirecting, otherwise there will be an endless loop.
    // This also covers a user who is already on their front page. The
    // exception is the front page that Drupal can't find in this language:
    // the redirect below goes to a URL of the same page that it can find.
    if ($user_page === $frontpage_path && !$is_frontpage_of_other_language) {
      return NULL;
    }

    // No language is given, so that the URL is built in the language of the
    // requested URL, with its prefix and its alias, like any link on a page.
    $redirect_url = Url::fromUserInput('/' . ltrim($user_page, '/'))->toString(TRUE);

    $cacheability = new CacheableMetadata();
    // Which page is requested and in which language decide the redirect,
    // together with the role of the user.
    $cacheability->setCacheContexts([
      'user.roles:' . $user_role,
      'url.path',
      'languages:' . LanguageInterface::TYPE_URL,
    ]);
    // The redirect changes with the site's front page and with the configured
    // alternative front pages. A changed alias is not covered.
    $cacheability->setCacheTags([
      'config:system.site',
      'config:alternative_frontpage_list',
    ]);

    $response = new CacheableRedirectResponse($redirect_url->getGeneratedUrl());
    $response->addCacheableDependency($cacheability);
    $response->addCacheableDependency($redirect_url);
    return $response;
  }

  /**
   * Gets the system path for a configured path that may be an alias.
   *
   * An alias only exists in the language it was made for, while the path is
   * configured once for all languages. The alias is therefore looked up in
   * the language of the requested URL first and then in every other language.
   *
   * @param string $path
   *   The configured path: a system path or an alias.
   *
   * @return string
   *   The system path, or the given path when it is not an alias.
   */
  private function getSystemPath(string $path): string {
    $url_langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
    $langcodes = array_unique([
      $url_langcode,
      ...array_keys($this->languageManager->getLanguages()),
    ]);

    foreach ($langcodes as $langcode) {
      $alias = $this->pathAliasRepository->lookupByAlias($path, $langcode);
      if ($alias !== NULL) {
        return $alias['path'];
      }
    }

    return $path;
  }

  /**
   * Get the alternative configs based on provided role.
   *
   * @param string $role
   *   The role machine name.
   *
   * @return string
   *   The entity ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getConfigIds(string $role): string {
    $entity = $this->entityTypeManager->getStorage('alternative_frontpage')
      ->getQuery()
      ->condition('roles_target_id', $role)
      ->accessCheck()
      ->execute();

    return (string) key((array) $entity);
  }

  /**
   * Get the correct Url Path for the landing page.
   *
   * @param string $role
   *   The role machine name.
   *
   * @return string|null
   *   The config entity URL path.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getConfigUrlPath(string $role): ?string {
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

  /**
   * Get redirected home page URL for current user roles.
   *
   * @param array $current_user_roles
   *   The current user roles.
   *
   * @return null|string[]
   *   The array of role and page URL.
   */
  private function getAlternativePageDataForCurrentUser(array $current_user_roles): ?array {
    $user_front_pages = $this->getAlternativePagesForUserRoles($current_user_roles);

    if (empty($user_front_pages)) {
      return NULL;
    }

    // This means that there is only one page configured for all current
    // user roles, and we can redirect them to this front page.
    if (count($user_front_pages) === 1) {
      return [
        'role' => (string) array_key_first($user_front_pages),
        'page' => (string) reset($user_front_pages),
      ];
    }

    // This means that the user has several roles and, accordingly, the
    // front pages are set for these several roles, in this case we check
    // the role weight and use a role with a higher weight.
    $current_user_role_weight = [];

    foreach ($current_user_roles as $current_user_role) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = $this->entityTypeManager->getStorage('user_role')->load($current_user_role);
      $current_user_role_weight[$current_user_role] = $role->getWeight();
    }

    $role_id_with_higher_weight = (string) array_search(max($current_user_role_weight), $current_user_role_weight, TRUE);
    $user_front_pages = $this->getAlternativePagesForUserRoles([$role_id_with_higher_weight]);

    return [
      'role' => (string) array_key_first($user_front_pages),
      'page' => (string) reset($user_front_pages),
    ];
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
      if (!empty($page = $this->getConfigUrlPath($role))) {
        $pages[$role] = $page;
      }
    }

    return $pages ?? [];
  }

}
