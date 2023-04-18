<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\social_core\StreamWrapper\SecretStream;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that the secret file stream-wrapper and controller function correctly.
 *
 * They should create expiring, unguessable and tamper-proof URLs.
 */
class SecretFileTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'file',
    'social_core',
  ];

  protected const TEST_FILE = "secret://test/file.txt";

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $site_path = $this->container->getParameter('site.path');
    assert(is_string($site_path));
    $this->setSetting('file_private_path', $site_path . '/private');

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');

    // Ensure there is a file to serve.
    $this->container->get("file_system")->mkdir(dirname(self::TEST_FILE), NULL, TRUE);
    file_put_contents(self::TEST_FILE, str_repeat("*", 100));
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) : void {
    parent::register($container);

    // Override the time class by something we can control.
    $container->getDefinition("datetime.time")->setClass(TestTimeService::class);

    // We must manually register the secret stream wrapper because we have a
    // chicken-egg problem in our test bootstrap where we need our container for
    // the site path to set the file path but the container won't have the
    // stream wrapper without the file path.
    $container->register('stream_wrapper.secret', SecretStream::class)
      ->addTag('stream_wrapper', ['scheme' => 'secret']);
  }

  /**
   * Test that a valid URL serves a cacheable file response.
   */
  public function testValidUrlServesFile() : void {
    $http_kernel = $this->container->get('http_kernel');

    $url = $this->container->get("file_url_generator")->generateString(self::TEST_FILE);

    $request = Request::create($url);
    $response = $http_kernel->handle($request);

    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), "Failed to fetch file at: " . $url);
    $this->assertInstanceOf(BinaryFileResponse::class, $response);
    $this->assertTrue($response->isCacheable(), "The secret response must be cacheable");
  }

  /**
   * Test that we properly bucket requests so that we can actually use caching.
   *
   * It's important that requests by two different people for the same file can
   * be served by the CDN without going back to Drupal, but the created URL
   * should at some point change to ensure they don't live forever.
   */
  public function testDifferentTimeCreatesBucketedUrl() : void {
    /** @var \Drupal\Tests\social_core\Kernel\TestTimeService $time */
    $time = $this->container->get("datetime.time");
    $bucket_length = $this->getBucketSetting();
    $current_time = $time->getRequestTime();

    // Start the test one-second past the previous bucket edge.
    $time->advanceTime(-($current_time % $bucket_length) + 1);

    // Create two URLs one second apart that should be in the same bucket.
    $first_url = $this->container->get("file_url_generator")->generateAbsoluteString(self::TEST_FILE);
    $time->advanceTime();
    $second_url = $this->container->get("file_url_generator")->generateAbsoluteString(self::TEST_FILE);

    $this->assertEquals($first_url, $second_url, "The URL should be the same within a bucket but this wasn't the case.");

    // Move halfway towards the next bucket, this should cause the URL to
    // change.
    $time->advanceTime($bucket_length / 2);
    $third_url = $this->container->get("file_url_generator")->generateAbsoluteString(self::TEST_FILE);

    $this->assertNotEquals($second_url, $third_url, "The URL past the halfway mark to a bucket should go to the next bucket ot allow the URL to live long enough.");
  }

  /**
   * Test that an expired URL causes a 404.
   */
  public function testOutdatedUrlCausesNotFound() : void {
    /** @var \Drupal\Tests\social_core\Kernel\TestTimeService $time */
    $time = $this->container->get("datetime.time");
    $http_kernel = $this->container->get('http_kernel');

    $url = $this->container->get("file_url_generator")->generateAbsoluteString(self::TEST_FILE);

    // Move the time to beyond the next bucket. We must move at least 1.5 times
    // + 1 because our request may be too close to the previous bucket line.
    $time->advanceTime((int) ceil($this->getBucketSetting() * 1.5) + 1);

    $request = Request::create($url);
    $response = $http_kernel->handle($request);

    $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
  }

  /**
   * Get the setting for the secret file bucket time.
   */
  protected function getBucketSetting() : int {
    return Settings::get("secret_file_bucket_time", 3600 /* 1 hour */);
  }

}
