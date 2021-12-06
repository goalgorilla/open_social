<?php

namespace Drupal\social_embed\Tests;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
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
  protected static $modules = [
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

    $this->installConfig(['user']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $settings = Settings::getAll();
    $settings['social_embed_flood_retries'] = 5;
    $settings['social_embed_flood_time_window'] = 10;
    new Settings($settings);

    // Give anonymous users permission to view test entities.
    if ($anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID)) {
      $anonymous_role->grantPermission('generate social embed content')
        ->save();
    }
  }

  /**
   * Test flood control generate embed content URL for LU.
   */
  public function testFloodControlForAuthenticatedUser(): void {
    // Create a user with the necessary permission.
    $this->setUpCurrentUser([], ['generate social embed content']);

    // URL needed for the request.
    $url = 'https://www.youtube.com/watch?v=ojafuCcUZzU';
    // Generate random UUID.
    $uuid = (new Php())->generate();

    // The maximum number of times each user can do this event per time window.
    $retries = Settings::get('social_embed_flood_retries');

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

  /**
   * Test flood control generate embed content URL for AN.
   */
  public function testFloodControlForAnonymousUser(): void {
    // URL needed for the request.
    $url = 'https://www.youtube.com/watch?v=ojafuCcUZzU';
    // Generate random UUID.
    $uuid = (new Php())->generate();

    // The maximum number of times each user can do this event per time window.
    $retries = Settings::get('social_embed_flood_retries');

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
