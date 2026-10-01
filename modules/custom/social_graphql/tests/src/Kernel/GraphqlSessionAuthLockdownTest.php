<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\consumers\Entity\ConsumerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\graphql\Entity\Server;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP checks for the GraphQL session-auth lockdown.
 *
 * @group social_graphql
 */
final class GraphqlSessionAuthLockdownTest extends SocialGraphQLTestBase {

  use OAuthTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'options',
    'image',
    'consumers',
    'simple_oauth',
    'simple_oauth_static_scope',
    'social_oauth',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('consumer');
    $this->installEntitySchema('oauth2_token');
    $this->installConfig(['simple_oauth', 'simple_oauth_static_scope']);
    $this->config('simple_oauth.settings')->set('scope_provider', 'static')->save();
    $this->setUpKeys();

    $this->ensureRole(RoleInterface::ANONYMOUS_ID, 'Anonymous');
    $this->ensureRole(RoleInterface::AUTHENTICATED_ID, 'Authenticated');
    $this->ensureRole('sitemanager', 'Site manager');
    // Kernel tests enable the module without running hook_install().
    $this->container->get('module_handler')->loadInclude('social_graphql', 'install');
    \social_graphql_install();
  }

  /**
   * A session cookie is rejected and the route lists only OAuth.
   *
   * The session id is unknown, like an expired cookie: the cookie provider
   * claims any request carrying the cookie name, so a stale cookie gets 403
   * instead of falling back to anonymous.
   */
  public function testSessionCookieIsForbidden(): void {
    $route_name = 'graphql.query.' . $this->server->id();
    $route = $this->container->get(RouteProviderInterface::class)->getRouteByName($route_name);
    self::assertSame(['oauth2'], $route->getOption('_auth'));

    $response = $this->postGraphql(session_id: 'session-cookie');
    self::assertSame(403, $response->getStatusCode(), (string) $response->getContent());
  }

  /**
   * A GraphQL server added after deploy is locked down as well.
   */
  public function testServerAddedLaterIsLockedDown(): void {
    $values = $this->server->toArray();
    unset($values['uuid']);
    Server::create([
      'name' => 'added_later',
      'endpoint' => '/graphql-added-later',
    ] + $values)->save();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['execute added_later arbitrary graphql requests']);
    $this->container->get('router.builder')->rebuild();

    $route = $this->container->get(RouteProviderInterface::class)->getRouteByName('graphql.query.added_later');
    self::assertSame(['oauth2'], $route->getOption('_auth'));

    // Positive control: the same endpoint answers without a cookie.
    $this->assertGraphqlData($this->postGraphql(endpoint: '/graphql-added-later'));
    $response = $this->postGraphql(session_id: 'session-cookie', endpoint: '/graphql-added-later');
    self::assertSame(403, $response->getStatusCode(), (string) $response->getContent());
  }

  /**
   * A request with no credentials stays anonymous and succeeds.
   */
  public function testAnonymousRequestWithoutCookieSucceeds(): void {
    $response = $this->postGraphql();
    $this->assertGraphqlData($response);
  }

  /**
   * A bearer token is accepted.
   */
  public function testBearerSucceeds(): void {
    $response = $this->postGraphql(authorization: $this->issueBearerAuthorization());
    $this->assertGraphqlData($response);
  }

  /**
   * OAuth wins when a bearer token and a session cookie are both sent.
   */
  public function testBearerWithCookieSucceeds(): void {
    $response = $this->postGraphql(
      session_id: 'session-cookie',
      authorization: $this->issueBearerAuthorization(),
    );
    $this->assertGraphqlData($response);
  }

  /**
   * Ticking the opt-out and rebuilding routes lets a session cookie through.
   */
  public function testOptOutAllowsSessionCookie(): void {
    $this->config('social_graphql.settings')
      ->set('allow_session_auth', TRUE)
      ->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    $route_name = 'graphql.query.' . $this->server->id();
    $route = $this->container->get(RouteProviderInterface::class)->getRouteByName($route_name);
    $auth = $route->getOption('_auth');
    self::assertIsArray($auth);
    self::assertContains('cookie', $auth);

    $response = $this->postGraphql(session_id: 'session-cookie');
    $this->assertGraphqlData($response);
  }

  /**
   * A site that has not run the update hook yet still rejects a cookie.
   */
  public function testMissingSettingForbidsSessionCookie(): void {
    $this->config('social_graphql.settings')
      ->clear('allow_session_auth')
      ->save();
    $this->container->get('router.builder')->rebuild();

    $route_name = 'graphql.query.' . $this->server->id();
    $route = $this->container->get(RouteProviderInterface::class)->getRouteByName($route_name);
    self::assertSame(['oauth2'], $route->getOption('_auth'));

    $response = $this->postGraphql(session_id: 'session-cookie');
    self::assertSame(403, $response->getStatusCode(), (string) $response->getContent());
  }

  /**
   * Install grants the settings form to the site manager role.
   */
  public function testInstallGrantsSettingsPermission(): void {
    $role = Role::load('sitemanager');
    self::assertNotNull($role);
    self::assertTrue($role->hasPermission('administer social_graphql settings'));
  }

  /**
   * The update writes FALSE only while the key is still unset.
   */
  public function testUpdatePreservesOptInAndDefaultsMissingKey(): void {
    $this->config('social_graphql.settings')
      ->set('allow_session_auth', TRUE)
      ->save();
    \social_graphql_update_26002();
    \social_graphql_update_26002();
    self::assertTrue($this->config('social_graphql.settings')->get('allow_session_auth'));

    $this->config('social_graphql.settings')
      ->clear('allow_session_auth')
      ->save();
    self::assertNull($this->config('social_graphql.settings')->get('allow_session_auth'));
    \social_graphql_update_26002();
    \social_graphql_update_26002();
    self::assertFalse($this->config('social_graphql.settings')->get('allow_session_auth'));
  }

  /**
   * The update grants the settings permission when the role lacks it.
   */
  public function testUpdateGrantsSettingsPermission(): void {
    $role = Role::load('sitemanager');
    self::assertNotNull($role);
    $role->revokePermission('administer social_graphql settings')->save();

    \social_graphql_update_26002();

    $reloaded = Role::load('sitemanager');
    self::assertNotNull($reloaded);
    self::assertTrue($reloaded->hasPermission('administer social_graphql settings'));
  }

  /**
   * Creates a role when kernel setup did not install it.
   */
  private function ensureRole(string $id, string $label): void {
    if (Role::load($id) === NULL) {
      Role::create(['id' => $id, 'label' => $label])->save();
    }
  }

  /**
   * Posts `{ __typename }` with an optional session cookie and bearer token.
   */
  private function postGraphql(?string $session_id = NULL, ?string $authorization = NULL, ?string $endpoint = NULL): Response {
    $endpoint ??= $this->server->get('endpoint');
    self::assertIsString($endpoint);
    $request = Request::create(
      $endpoint,
      'POST',
      [],
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      json_encode(['query' => '{ __typename }'], JSON_THROW_ON_ERROR),
    );
    if ($session_id !== NULL) {
      $session_configuration = $this->container->get('session_configuration');
      $name = $session_configuration->getOptions($request)['name'];
      self::assertIsString($name);
      $request->cookies->set($name, $session_id);
    }
    if ($authorization !== NULL) {
      $request->headers->set('Authorization', $authorization);
    }
    return $this->container->get('http_kernel')->handle($request);
  }

  /**
   * Issues a client-credentials bearer header for the GraphQL scope.
   */
  private function issueBearerAuthorization(): string {
    $actor = $this->createUser();
    self::assertNotFalse($actor);
    $client_secret = $this->randomString();
    $client = $this->container->get('entity_type.manager')
      ->getStorage('consumer')
      ->create([
        'client_id' => 'remote_system_' . $this->randomMachineName(),
        'label' => 'A Remote System',
        'grant_types' => ['client_credentials'],
        'scopes' => [['scope_id' => 'graphql:use']],
        'user_id' => ['target_id' => $actor->id()],
        'secret' => $client_secret,
        'redirect' => 'foo://bar',
      ]);
    self::assertInstanceOf(ConsumerInterface::class, $client);
    $client->save();

    $token_request = Request::create('/oauth/token', 'POST', [
      'grant_type' => 'client_credentials',
      'client_id' => $client->getClientId(),
      'client_secret' => $client_secret,
    ]);
    $token_response = $this->container->get('http_kernel')->handle($token_request);
    self::assertSame(200, $token_response->getStatusCode(), (string) $token_response->getContent());
    /** @var array{access_token: string, token_type: string} $token_data */
    $token_data = json_decode((string) $token_response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
    self::assertArrayNotHasKey('error', $token_data);

    return $token_data['token_type'] . ' ' . $token_data['access_token'];
  }

  /**
   * Asserts a successful GraphQL payload for `{ __typename }`.
   */
  private function assertGraphqlData(Response $response): void {
    self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());
    $payload = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
    self::assertIsArray($payload);
    self::assertSame(['__typename' => 'Query'], $payload['data'] ?? NULL);
    self::assertArrayNotHasKey('errors', $payload);
  }

}
