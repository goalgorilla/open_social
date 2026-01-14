<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\consumers\Entity\ConsumerInterface;
use Drupal\simple_oauth\Authentication\TokenAuthUserInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides helper methods to verify implementations of OAuth in GraphQL APIs.
 */
trait GraphQLOAuthTestTrait {

  /**
   * Ensure the current user a machine-to-machine authenticated user.
   *
   * @param array $client_scopes
   *   The client scopes that the service possesses.
   * @param string $name
   *   The human-readable name of the service.
   * @param array $user_values
   *   Any User entity specific values that should be set.
   *
   * @return \Drupal\simple_oauth\Authentication\TokenAuthUserInterface
   *   The authenticated user that's set as the current user.
   */
  protected function actAsClientCredentialsWithScopes(array $client_scopes, string $name = "Machine", array $user_values = []) : TokenAuthUserInterface {
    $actor = $this->createUser([], $name, FALSE, $user_values);
    assert($actor !== FALSE);
    $token_user = $this->getClientCredentialsTokenAuthUser((int) $actor->id(), $client_scopes);
    $this->setCurrentUser($token_user);

    return $token_user;
  }

  /**
   * Ensure the current user is an on-behalf-of authenticated user.
   *
   * @param array $client_scopes
   *   The scopes that the client should have.
   * @param \Drupal\user\UserInterface $user
   *   The user that should authorize the application.
   *
   * @return \Drupal\simple_oauth\Authentication\TokenAuthUserInterface
   *   The authenticated application on behalf of the user.
   */
  protected function actAsAuthorizationCodeWithScopes(array $client_scopes, UserInterface $user) : TokenAuthUserInterface {
    assert($user->hasPermission('grant simple_oauth codes'), "The user authorizing the application requires the 'grant simple_oauth codes' permission.");
    $this->setCurrentUser($user);
    $token_user = $this->getAuthorizationCodeTokenAuthUser($client_scopes);
    $this->setCurrentUser($token_user);

    return $token_user;
  }

  /**
   * Get a system to system user that is authenticated using an OAuth token.
   *
   * The return value of this function is what simple_oauth would set the
   * currentUser to for system-to-system requests authorized by OAuth.
   * That's because Drupal always requires actions to be performed by an
   * implementation of the User entity even if they're not a real user.
   *
   * @param int $acting_user_id
   *   The ID of the user which will be shown as performer of the actions by the
   *   system.
   * @param array $scopes
   *   The scopes for the interactions.
   *
   * @return \Drupal\simple_oauth\Authentication\TokenAuthUserInterface
   *   An authenticated "user" which signifies the system-to-system user
   *   interaction.
   */
  private function getClientCredentialsTokenAuthUser(int $acting_user_id, array $scopes = []) : TokenAuthUserInterface {
    $entityTypeManager = $this->container->get("entity_type.manager");

    // Create our Oauth2 client.
    $clientSecret = $this->randomString();
    $client = $entityTypeManager
      ->getStorage("consumer")
      ->create([
        'client_id' => 'remote_system',
        'label' => 'A Remote System',
        'grant_types' => [
          'client_credentials',
        ],
        'scopes' => array_map(fn (string $scope) => ["scope_id" => $scope], $scopes),
        'user_id' => ['target_id' => $acting_user_id],
        'secret' => $clientSecret,
        // Until https://www.drupal.org/project/simple_oauth/issues/3416419.
        'redirect' => 'foo://bar',
      ]);
    assert($client instanceof ConsumerInterface);
    $violations = $client->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The client is invalid: {$violations->__toString()}");
    }
    $client->save();

    // Request a token.
    $request = Request::create("/oauth/token", "POST", [
      "grant_type" => "client_credentials",
      "client_id" => $client->getClientId(),
      "client_secret" => $clientSecret,
    ]);
    $httpKernel = $this->container->get('http_kernel');

    $response = $httpKernel->handle($request);
    self::assertTrue($response->isOk(), (string) $response->getContent());
    /** @var array{access_token: string, expires_in: int, token_type: string, scope: string} $token_data */
    $token_data = json_decode($response->getContent() ?: "", TRUE, 512, JSON_THROW_ON_ERROR);
    self::assertArrayNotHasKey("error", $token_data, "There was an error requesting the OAuth token.");

    // Let the authentication provider turn the token into the right user,
    // this is easier than doing it ourselves.
    $account_request = Request::create("/", "GET", [], [], [], ['HTTP_AUTHORIZATION' => "{$token_data["token_type"]} {$token_data["access_token"]}"]);

    $token_user = $this->container->get("simple_oauth.authentication.simple_oauth")->authenticate($account_request);
    assert($token_user instanceof TokenAuthUserInterface);
    return $token_user;
  }

  /**
   * Get an on-behalf-of user that is authenticated using an OAuth token.
   *
   * The return value of this function is what simple_oauth would set the
   * currentUser to for on-behalf-of (authorization_code) requests authorized
   * by a user through OAuth.
   *
   * @param array $scopes
   *   The scopes for the interactions.
   *
   * @return \Drupal\simple_oauth\Authentication\TokenAuthUserInterface
   *   An authenticated "user" which signifies the on-behalf-of user
   *   interaction.
   */
  private function getAuthorizationCodeTokenAuthUser(array $scopes = []) : TokenAuthUserInterface {
    $entityTypeManager = $this->container->get("entity_type.manager");

    // Create our Oauth2 client.
    $clientSecret = $this->randomString();
    $client = $entityTypeManager
      ->getStorage("consumer")
      ->create([
        'client_id' => 'remote_system',
        'label' => 'A Remote System',
        'grant_types' => [
          'authorization_code',
        ],
        'automatic_authorization' => TRUE,
        'secret' => $clientSecret,
        // Until https://www.drupal.org/project/simple_oauth/issues/3416419.
        'redirect' => 'foo://bar',
      ]);
    assert($client instanceof ConsumerInterface);
    $violations = $client->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The client is invalid: {$violations->__toString()}");
    }
    $client->save();

    /** @var \Symfony\Component\HttpKernel\HttpKernel $httpKernel */
    $httpKernel = $this->container->get('http_kernel');

    // Request the user to be authorized.
    $request = Request::create("/oauth/authorize", "GET", [
      "response_type" => "code",
      "client_id" => $client->getClientId(),
      "scope" => implode(' ', $scopes),
      "state" => 'foo',
    ]);
    $response = $httpKernel->handle($request);
    self::assertTrue($response->isRedirection(), (string) $response->getContent());

    $redirect_url = $response->headers->get('Location');
    self::assertIsString($redirect_url, "Authorize request did not provide a Location header");
    $query_string = [];
    parse_str((string) parse_url($redirect_url, PHP_URL_QUERY), $query_string);

    self::assertIsString($query_string['code'], "The location redirect from authorization didn't contain a code.");

    // Request a token.
    $request = Request::create("/oauth/token", "POST", [
      "grant_type" => "authorization_code",
      "client_id" => $client->getClientId(),
      "client_secret" => $clientSecret,
      "code" => $query_string['code'],
    ]);

    $response = $httpKernel->handle($request);
    self::assertTrue($response->isOk(), (string) $response->getContent());
    /** @var array{access_token: string, expires_in: int, token_type: string, scope: string} $token_data */
    $token_data = json_decode($response->getContent() ?: "", TRUE, 512, JSON_THROW_ON_ERROR);
    self::assertArrayNotHasKey("error", $token_data, "There was an error requesting the OAuth token.");

    // Let the authentication provider turn the token into the right user,
    // this is easier than doing it ourselves.
    $account_request = Request::create("/", "GET", [], [], [], ['HTTP_AUTHORIZATION' => "{$token_data["token_type"]} {$token_data["access_token"]}"]);

    $token_user = $this->container->get("simple_oauth.authentication.simple_oauth")->authenticate($account_request);
    assert($token_user instanceof TokenAuthUserInterface);
    return $token_user;
  }

}
