<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\KernelTests\KernelTestBase;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Shared bootstrap and HTTP helpers for signed_file_upload kernel tests.
 */
abstract class SignedFileUploadTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'file',
    'signed_file_upload',
  ];

  /**
   * A separate lock backend instance to simulate locks.
   */
  private ?LockBackendInterface $lockBackend = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('signed_file_upload_session');
    $this->installConfig(['signed_file_upload']);

    // Ensure we have a proper lock backend so we can test locks, rather than
    // one that always allows all locks.
    $this->container->set('lock', new DatabaseLockBackend($this->container->get('database')));
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->register('stream_wrapper.private', PrivateStream::class)
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem(): void {
    parent::setUpFilesystem();

    mkdir($this->siteDirectory . '/private', 0775, TRUE);
    $this->setSetting('file_private_path', $this->siteDirectory . '/private');
  }

  /**
   * Create an anonymous account to test with.
   *
   * @return \Drupal\Core\Session\UserSession
   *   An anonymous user session.
   */
  protected function account(): UserSession {
    return new UserSession(['uid' => 0]);
  }

  /**
   * Get the HTTP Kernel to execute tests against.
   *
   * @return \Symfony\Component\HttpKernel\HttpKernelInterface
   *   The HTTP Kernel to execute tests against.
   */
  protected function httpKernel(): HttpKernelInterface {
    return $this->container->get('http_kernel');
  }

  /**
   * Acquire a lock on a file to test that it properly conflicts.
   *
   * Ensures the lock is setup in a separate lock backend instance so that the
   * main lock backend in the container thinks the lock is already taken
   * (by seeing it in the database).
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The session to acquire a lock for.
   */
  protected function simulateLockedFile(UploadSession $session) : void {
    $this->assertTrue(
      $this->getParallelLockBackend()
        ->acquire("suf_upload_session_lock." . $session->sessionId),
      "Failed to acquire lock in test.",
    );
  }

  /**
   * A parallel lock backend to simulate other processes taking locks.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   *   The lock backend.
   */
  private function getParallelLockBackend() : LockBackendInterface {
    return $this->lockBackend ??= new DatabaseLockBackend($this->container->get('database'));
  }

  /**
   * Dispatches a subrequest through the HTTP kernel.
   *
   * @param string $method
   *   The method to use for the request.
   * @param string $path
   *   The path to make the request against.
   * @param string $content
   *   The contents to send in the body.
   * @param array<string, string> $headers
   *   Header names (e.g. Tus-Resumable) to values.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response of the request.
   *
   * @throws \Exception
   *   In case there was an error completing the request.
   */
  protected function tusRequest(string $method, string $path, string $content = '', array $headers = []): Response {
    $request = Request::create($path, $method, [], [], [], [], $content);
    $request->headers->add($headers);
    return $this->httpKernel()->handle($request);
  }

  /**
   * Asserts standard tus headers in a response.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to assert against.
   */
  protected function assertTusHeaders(Response $response): void {
    $this->assertSame('1.0.0', $response->headers->get('Tus-Version'));
    $this->assertTusResumableHeader($response);
    $this->assertTusExtensionHeaderContains($response, 'expiration');
    $this->assertTusExtensionHeaderContains($response, 'termination');
  }

  /**
   * Assert the tus resumable header is present with the correct value.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to assert against.
   */
  protected function assertTusResumableHeader(Response $response): void {
    $this->assertSame('1.0.0', $response->headers->get('Tus-Resumable'));
  }

  /**
   * Assert that the Tus-Extension header contains the expected value.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to assert against.
   * @param 'expiration'|'termination'|non-empty-string $name
   *   The extension to expect.
   */
  protected function assertTusExtensionHeaderContains(Response $response, string $name): void {
    $raw = $response->headers->get('Tus-Extension', '');
    $ext = array_map('trim', explode(',', $raw ?? ''));
    $this->assertContains($name, $ext, sprintf('Tus-Extension "%s" should list %s.', $raw, $name));
  }

  /**
   * Asserts a stream wrapper URI resolves to an existing local file.
   *
   * @param string $uri
   *   The URI to resolve.
   */
  protected function assertUriFileExists(string $uri): void {
    $path = $this->container->get('file_system')->realpath($uri);
    $this->assertNotFalse($path, sprintf('Expected a real path for %s', $uri));
    $this->assertFileExists($path);
  }

  /**
   * Asserts a stream wrapper URI does not exist on disk (after cleanup).
   *
   * @param string $uri
   *   The URI to assert against.
   */
  protected function assertUriFileDoesNotExist(string $uri): void {
    $path = $this->container->get('file_system')->realpath($uri);
    if ($path !== FALSE) {
      $this->assertFileDoesNotExist($path);
    }
  }

}
