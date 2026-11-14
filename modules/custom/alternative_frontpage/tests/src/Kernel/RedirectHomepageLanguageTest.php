<?php

declare(strict_types=1);

namespace Drupal\Tests\alternative_frontpage\Kernel;

use Drupal\alternative_frontpage\Entity\AlternativeFrontpage;
use Drupal\alternative_frontpage\EventSubscriber\RedirectHomepageSubscriber;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;

// The German aliases and prefix of the test site.
// cspell:ignore deutsch startpage startseite willkommen

/**
 * Tests the front page redirect, in particular on sites with several languages.
 *
 * The public landing page is node 1 and the front page for members is node 2,
 * with the aliases "/startseite" in German and "/startpage" in English.
 *
 * Unless a test says otherwise the languages are set up as in PROD-37055:
 * English is the default language and has the prefix "en", German has no
 * prefix and is the selected fallback language.
 *
 * @group alternative_frontpage
 */
class RedirectHomepageLanguageTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'language',
    'path',
    'path_alias',
    'alternative_frontpage',
  ];

  /**
   * {@inheritdoc}
   *
   * Adding a language needs a rebuild of the container. After that rebuild the
   * config schema of a module that lives in an install profile is no longer
   * found, because the test doesn't install the profile.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['system', 'user', 'filter', 'node', 'language']);

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    Role::create(['id' => 'administrator', 'label' => 'Administrator'])->save();
    Role::load(AccountInterface::ANONYMOUS_ROLE)?->grantPermission('access content')->save();
    Role::load(AccountInterface::AUTHENTICATED_ROLE)?->grantPermission('access content')->save();
    // Keep user 1 out of the way, it has every permission.
    $this->createUser();

    Node::create(['type' => 'page', 'title' => 'Public landing page', 'langcode' => 'en'])->save();
    Node::create(['type' => 'page', 'title' => 'Start page', 'langcode' => 'en'])->save();
    PathAlias::create(['path' => '/node/1', 'alias' => '/welcome', 'langcode' => 'en'])->save();
    PathAlias::create(['path' => '/node/2', 'alias' => '/startpage', 'langcode' => 'en'])->save();

    $this->config('system.site')->set('page.front', '/node/1')->save();
  }

  /**
   * Adds German next to English, with German aliases for the pages.
   *
   * @param array<string, string> $prefixes
   *   The URL prefix of each language, keyed by language code.
   * @param string[] $interface_detection
   *   The ids of the methods that detect the interface language, in order.
   */
  protected function setUpGerman(
    array $prefixes = ['en' => 'en', 'de' => ''],
    array $interface_detection = ['language-user', 'language-url', 'language-selected'],
  ): void {
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->config('language.negotiation')
      ->set('url.source', 'path_prefix')
      ->set('url.prefixes', $prefixes)
      ->set('selected_langcode', 'de')
      ->save();
    $this->config('language.types')
      ->set('configurable', ['language_interface', 'language_content'])
      ->set('negotiation.language_interface.enabled', array_flip($interface_detection))
      ->set('negotiation.language_content.enabled', array_flip([
        'language-url',
        'language-interface',
        'language-selected',
      ]))
      ->save();

    PathAlias::create(['path' => '/node/1', 'alias' => '/willkommen', 'langcode' => 'de'])->save();
    PathAlias::create(['path' => '/node/2', 'alias' => '/startseite', 'langcode' => 'de'])->save();

    // The language module only registers its path processors on a site with
    // more than one language, which is decided when the container is built.
    // The rebuild also replaces the container of this test.
    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Configures the front pages for anonymous and for authenticated users.
   *
   * @param string $anonymous_path
   *   The path of the front page for anonymous users.
   * @param string $authenticated_path
   *   The path of the front page for authenticated users.
   */
  protected function setUpFrontPages(string $anonymous_path = '/node/1', string $authenticated_path = '/node/2'): void {
    AlternativeFrontpage::create([
      'id' => 'public',
      'label' => 'Frontpage for Public',
      'path' => $anonymous_path,
      'roles_target_id' => AccountInterface::ANONYMOUS_ROLE,
    ])->save();
    AlternativeFrontpage::create([
      'id' => 'members',
      'label' => 'Frontpage for Members',
      'path' => $authenticated_path,
      'roles_target_id' => AccountInterface::AUTHENTICATED_ROLE,
    ])->save();
  }

  /**
   * Asks the subscriber for the redirect for a request.
   *
   * @param string $path
   *   The path that is requested.
   * @param string|null $user
   *   Who makes the request: NULL for an anonymous user, or the preferred
   *   language of a member ("de"), with "admin-" in front of it for an
   *   administrator ("admin-de").
   *
   * @return \Drupal\Core\Cache\CacheableRedirectResponse|null
   *   The redirect, or NULL if the request is not redirected.
   */
  protected function getRedirect(string $path, ?string $user): ?CacheableRedirectResponse {
    if ($user === NULL) {
      $this->setCurrentUser(new AnonymousUserSession());
    }
    else {
      $is_administrator = str_starts_with($user, 'admin-');
      $account = $this->createUser(values: [
        'preferred_langcode' => $is_administrator ? substr($user, 6) : $user,
      ]);
      self::assertInstanceOf(UserInterface::class, $account);
      if ($is_administrator) {
        $account->addRole('administrator');
        $account->save();
      }
      $this->setCurrentUser($account);
    }

    $request = Request::create($path);
    $this->container->get('request_stack')->push($request);
    // Forget the languages that were negotiated while the test was set up, so
    // that they are negotiated for this request and this user.
    $this->container->get('language_negotiator')->setCurrentUser($this->container->get('current_user'));
    $this->container->get('language_manager')->reset();

    return $this->container->get('alternative_frontpage.redirect_homepage')->getHomepageRedirect($request);
  }

  /**
   * Asserts where a request is redirected to.
   *
   * @param string|null $expected
   *   The expected target of the redirect, or NULL for no redirect.
   * @param string $path
   *   The path that is requested.
   * @param string|null $user
   *   Who makes the request, see ::getRedirect().
   */
  protected function assertRedirect(?string $expected, string $path, ?string $user): void {
    self::assertSame($expected, $this->getRedirect($path, $user)?->getTargetUrl());
  }

  /**
   * Tests the redirect when the user's language is detected before the URL's.
   *
   * @dataProvider provideUserBeforeUrl
   */
  public function testUserBeforeUrl(string $path, ?string $user, ?string $expected): void {
    $this->setUpGerman();
    $this->setUpFrontPages();
    $this->assertRedirect($expected, $path, $user);
  }

  /**
   * Data provider for testUserBeforeUrl().
   *
   * @return array<string, array{string, string|null, string|null}>
   *   The path, the user and the expected redirect.
   */
  public static function provideUserBeforeUrl(): array {
    return [
      'German member on /en' => ['/en', 'de', '/en/startpage'],
      'German member on /' => ['/', 'de', '/startseite'],
      'English member on /en' => ['/en', 'en', '/en/startpage'],
      'English member on /' => ['/', 'en', '/startseite'],
      'German member on /en/node/1' => ['/en/node/1', 'de', '/en/startpage'],
      'German member on /node/1' => ['/node/1', 'de', '/startseite'],
      'German member on /en/welcome' => ['/en/welcome', 'de', '/en/startpage'],
      'German member on /willkommen' => ['/willkommen', 'de', '/startseite'],
      'German member on /en/startpage' => ['/en/startpage', 'de', NULL],
      'German member on /startseite' => ['/startseite', 'de', NULL],
      'German member on /en/node/2' => ['/en/node/2', 'de', NULL],
      'German member on /en/user' => ['/en/user', 'de', NULL],
      'German administrator on /en' => ['/en', 'admin-de', '/en/startpage'],
      'German administrator on /' => ['/', 'admin-de', '/startseite'],
      'English administrator on /en' => ['/en', 'admin-en', '/en/startpage'],
      'English administrator on /' => ['/', 'admin-en', '/startseite'],
      'German administrator on /en/startpage' => ['/en/startpage', 'admin-de', NULL],
      'Anonymous on /en' => ['/en', NULL, NULL],
      'Anonymous on /' => ['/', NULL, NULL],
    ];
  }

  /**
   * Tests the redirect when the URL's language is detected before the user's.
   *
   * @dataProvider provideUrlBeforeUser
   */
  public function testUrlBeforeUser(string $path, ?string $user, ?string $expected): void {
    $this->setUpGerman(interface_detection: ['language-url', 'language-user', 'language-selected']);
    $this->setUpFrontPages();
    $this->assertRedirect($expected, $path, $user);
  }

  /**
   * Data provider for testUrlBeforeUser().
   *
   * @return array<string, array{string, string|null, string|null}>
   *   The path, the user and the expected redirect.
   */
  public static function provideUrlBeforeUser(): array {
    return [
      'German member on /en' => ['/en', 'de', '/en/startpage'],
      'German member on /' => ['/', 'de', '/startseite'],
      'English member on /' => ['/', 'en', '/startseite'],
      'German administrator on /en' => ['/en', 'admin-de', '/en/startpage'],
    ];
  }

  /**
   * Tests front pages that are configured as an alias of one language.
   *
   * Saving the front page for anonymous users on its form makes its path the
   * site's front page, as it was typed.
   *
   * @dataProvider provideConfiguredAsAlias
   */
  public function testConfiguredAsAlias(string $path, ?string $user, ?string $expected): void {
    $this->setUpGerman();
    $this->config('system.site')->set('page.front', '/willkommen')->save();
    $this->setUpFrontPages('/willkommen', '/startseite');
    $this->assertRedirect($expected, $path, $user);
  }

  /**
   * Data provider for testConfiguredAsAlias().
   *
   * @return array<string, array{string, string|null, string|null}>
   *   The path, the user and the expected redirect.
   */
  public static function provideConfiguredAsAlias(): array {
    return [
      'English member on /en' => ['/en', 'en', '/en/startpage'],
      'German member on /en' => ['/en', 'de', '/en/startpage'],
      'German member on /' => ['/', 'de', '/startseite'],
      'German member on /willkommen' => ['/willkommen', 'de', '/startseite'],
      'German member on /en/welcome' => ['/en/welcome', 'de', '/en/startpage'],
      'German member on /en/startpage' => ['/en/startpage', 'de', NULL],
      'German administrator on /en' => ['/en', 'admin-de', '/en/startpage'],
      // Drupal looks for the German alias in English and doesn't find it, so
      // the visitor is sent to the same page by its English alias.
      'Anonymous on /en' => ['/en', NULL, '/en/welcome'],
      'Anonymous on /en/welcome' => ['/en/welcome', NULL, NULL],
      'Anonymous on /' => ['/', NULL, NULL],
      'Anonymous on /willkommen' => ['/willkommen', NULL, NULL],
    ];
  }

  /**
   * Tests a prefix that is not the language code.
   *
   * @dataProvider provideCustomPrefix
   */
  public function testCustomPrefix(string $path, ?string $user, ?string $expected): void {
    $this->setUpGerman(prefixes: ['en' => 'en', 'de' => 'deutsch']);
    $this->setUpFrontPages();
    $this->assertRedirect($expected, $path, $user);
  }

  /**
   * Data provider for testCustomPrefix().
   *
   * @return array<string, array{string, string|null, string|null}>
   *   The path, the user and the expected redirect.
   */
  public static function provideCustomPrefix(): array {
    return [
      'English member on /deutsch' => ['/deutsch', 'en', '/deutsch/startseite'],
      'German member on /deutsch' => ['/deutsch', 'de', '/deutsch/startseite'],
      'German member on /deutsch/node/1' => ['/deutsch/node/1', 'de', '/deutsch/startseite'],
      'German member on /en' => ['/en', 'de', '/en/startpage'],
      'German member on /deutsch/startseite' => ['/deutsch/startseite', 'de', NULL],
      // "de" is the language code but not a prefix, so not the front page.
      'German member on /de' => ['/de', 'de', NULL],
      'Anonymous on /deutsch' => ['/deutsch', NULL, NULL],
    ];
  }

  /**
   * Tests a site with several languages that have no prefix in the URL.
   *
   * The language of the URL is then the site's default language.
   *
   * @dataProvider provideWithoutPrefixes
   */
  public function testWithoutPrefixes(string $path, ?string $user, ?string $expected): void {
    $this->setUpGerman(prefixes: ['en' => '', 'de' => '']);
    $this->setUpFrontPages();
    $this->assertRedirect($expected, $path, $user);
  }

  /**
   * Data provider for testWithoutPrefixes().
   *
   * @return array<string, array{string, string|null, string|null}>
   *   The path, the user and the expected redirect.
   */
  public static function provideWithoutPrefixes(): array {
    return [
      'German member on /' => ['/', 'de', '/startpage'],
      'English member on /' => ['/', 'en', '/startpage'],
      'German member on /node/1' => ['/node/1', 'de', '/startpage'],
      'German member on /startpage' => ['/startpage', 'de', NULL],
      'German administrator on /' => ['/', 'admin-de', '/startpage'],
      'Anonymous on /' => ['/', NULL, NULL],
    ];
  }

  /**
   * Tests a site with one language.
   *
   * @dataProvider provideSingleLanguage
   */
  public function testSingleLanguage(string $path, ?string $user, ?string $expected): void {
    $this->setUpFrontPages();
    $this->assertRedirect($expected, $path, $user);
  }

  /**
   * Data provider for testSingleLanguage().
   *
   * @return array<string, array{string, string|null, string|null}>
   *   The path, the user and the expected redirect.
   */
  public static function provideSingleLanguage(): array {
    return [
      'Member on /' => ['/', 'en', '/startpage'],
      'Member on /node/1' => ['/node/1', 'en', '/startpage'],
      'Member on /welcome' => ['/welcome', 'en', '/startpage'],
      'Member on /startpage' => ['/startpage', 'en', NULL],
      'Member on /node/2' => ['/node/2', 'en', NULL],
      // There is no language prefix, so this is not the front page.
      'Member on /en' => ['/en', 'en', NULL],
      'Administrator on /' => ['/', 'admin-en', '/startpage'],
      'Anonymous on /' => ['/', NULL, NULL],
    ];
  }

  /**
   * Tests a site with one language and front pages configured as aliases.
   *
   * @dataProvider provideSingleLanguageConfiguredAsAlias
   */
  public function testSingleLanguageConfiguredAsAlias(string $path, ?string $user, ?string $expected): void {
    $this->config('system.site')->set('page.front', '/welcome')->save();
    $this->setUpFrontPages('/welcome', '/startpage');
    $this->assertRedirect($expected, $path, $user);
  }

  /**
   * Data provider for testSingleLanguageConfiguredAsAlias().
   *
   * @return array<string, array{string, string|null, string|null}>
   *   The path, the user and the expected redirect.
   */
  public static function provideSingleLanguageConfiguredAsAlias(): array {
    return [
      'Member on /' => ['/', 'en', '/startpage'],
      'Member on /welcome' => ['/welcome', 'en', '/startpage'],
      'Member on /node/1' => ['/node/1', 'en', '/startpage'],
      'Member on /startpage' => ['/startpage', 'en', NULL],
      'Administrator on /' => ['/', 'admin-en', '/startpage'],
      'Anonymous on /' => ['/', NULL, NULL],
      'Anonymous on /welcome' => ['/welcome', NULL, NULL],
    ];
  }

  /**
   * Tests that anonymous users are redirected to a front page of their own.
   */
  public function testAnonymousFrontPage(): void {
    $this->setUpGerman();
    // The site's front page is the one for members, as on a closed community.
    $this->config('system.site')->set('page.front', '/node/2')->save();
    $this->setUpFrontPages('/node/1', '/node/2');

    $this->assertRedirect('/en/welcome', '/en', NULL);
    $this->assertRedirect('/willkommen', '/', NULL);
    $this->assertRedirect(NULL, '/en/welcome', NULL);
    $this->assertRedirect(NULL, '/en', 'de');
  }

  /**
   * Tests that administrators stay when members have no front page.
   */
  public function testAdministratorWithoutAuthenticatedFrontPage(): void {
    $this->setUpGerman();
    AlternativeFrontpage::create([
      'id' => 'public',
      'label' => 'Frontpage for Public',
      'path' => '/node/2',
      'roles_target_id' => AccountInterface::ANONYMOUS_ROLE,
    ])->save();

    $this->assertRedirect(NULL, '/en', 'admin-de');
    $this->assertRedirect(NULL, '/en', 'de');
    $this->assertRedirect('/en/startpage', '/en', NULL);
  }

  /**
   * Tests that nothing happens on a site without a front page.
   */
  public function testWithoutSiteFrontPage(): void {
    $this->setUpGerman();
    $this->setUpFrontPages();
    $this->config('system.site')->set('page.front', '')->save();

    $this->assertRedirect(NULL, '/', 'de');
    $this->assertRedirect(NULL, '/en', 'de');
  }

  /**
   * Tests what the redirect says about when it can be reused.
   */
  public function testCacheability(): void {
    $this->setUpGerman();
    $this->setUpFrontPages();

    $redirect = $this->getRedirect('/en', 'de');
    self::assertNotNull($redirect);
    $cacheability = $redirect->getCacheableMetadata();
    $contexts = $cacheability->getCacheContexts();
    self::assertContains('user.roles:authenticated', $contexts);
    self::assertContains('url.path', $contexts);
    self::assertContains('languages:language_url', $contexts);
    self::assertNotContains('languages:language_interface', $contexts);
    self::assertContains('config:system.site', $cacheability->getCacheTags());
    self::assertContains('config:alternative_frontpage_list', $cacheability->getCacheTags());

    $redirect = $this->getRedirect('/en', 'admin-de');
    self::assertNotNull($redirect);
    self::assertContains('user.roles:administrator', $redirect->getCacheableMetadata()->getCacheContexts());
  }

  /**
   * Tests that the subscriber still runs before routing and the page caches.
   */
  public function testPriority(): void {
    self::assertEquals(
      [KernelEvents::REQUEST => [['checkForHomepageRedirect', 280]]],
      RedirectHomepageSubscriber::getSubscribedEvents(),
    );
  }

}
