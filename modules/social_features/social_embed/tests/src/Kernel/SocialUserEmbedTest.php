<?php

namespace Drupal\social_embed\Tests;

use Drupal\Component\Uuid\Php;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test user embed functionality.
 *
 * @group user
 */
class SocialUserEmbedTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'role_delegation',
    'profile',
    'embed',
    'url_embed',
    'social_editor',
    'social_user',
    'social_profile',
    'social_embed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
  }

  /**
   * Test generate embed content of a given URL.
   *
   * Test maximum number of times each user can generate embed content of a
   * given URL.
   */
  public function testSocialUserEmbed(): void {
    // Create a user with the necessary permission.
    /** @var \Drupal\Core\Session\AccountInterface $user */
    $user = $this->createUser(['generate social embed content']);
    // Set created user as the current user.
    \Drupal::currentUser()->setAccount($user);

    // URL needed for the request.
    $url = 'https://www.youtube.com/watch?v=kgE9QNX8f3c';
    // Generate random UUID.
    $uuid = (new Php())->generate();

    // The maximum number of times each user can do this event per time window.
    $retries = 50;

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = $this->container->get('http_kernel');

    for ($i = 0; $i <= $retries; $i++) {
      // Create request.
      $request = Request::create("/api/opensocial/social-embed/generate?url=$url&uuid=$uuid");
      // Handle request.
      $response = $http_kernel->handle($request);

      // All requests should be ok except last one as it will be $retries + 1.
      $status_code = ($i === $retries) ? Response::HTTP_FORBIDDEN : Response::HTTP_OK;
      $this->assertEqual($response->getStatusCode(), $status_code);
    }
  }

}
