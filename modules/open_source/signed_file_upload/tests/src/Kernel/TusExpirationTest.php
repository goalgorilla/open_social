<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Symfony\Component\HttpFoundation\Response;

/**
 * Tus Expiration: Upload-Expires; time-based expiry; staged cleanup.
 *
 * @group signed_file_upload
 */
class TusExpirationTest extends SignedFileUploadWithEntityDestinationTestBase {

  /**
   * A request time service that we can manipulate.
   */
  protected MutableRequestTime $requestTime;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->requestTime = new MutableRequestTime(2_000_000_000);
    parent::setUp();
    $this->container->set('datetime.time', $this->requestTime);
  }

  /**
   * Test that OPTIONS response advertises the expiration extension.
   */
  public function testOptionsAdvertisesExpirationCapability(): void {
    $path = "/api/tus";
    $response = $this->tusRequest('OPTIONS', $path);
    $this->assertTusHeaders($response);
    $ext = array_map('trim', explode(',', $response->headers->get('Tus-Extension', '') ?? ''));
    $this->assertContains('expiration', $ext);
  }

  /**
   * Test that HEAD includes a well-formed RFC 7231 Upload-Expires header.
   */
  public function testHeadRespondsWithUploadExpires(): void {
    $grant = $this->beginSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $expires = $response->headers->get('Upload-Expires');
    $this->assertNotEmpty($expires);
    $parsed = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $expires);
    $this->assertInstanceOf(\DateTimeImmutable::class, $parsed);
  }

  /**
   * PATCH responses include Upload-Expires while upload is unfinished.
   */
  public function testPatchIncludesUploadExpiresWhileIncomplete(): void {
    $grant = $this->beginSessionKnownLength(100);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, 'x', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $expires = $response->headers->get('Upload-Expires');
    $this->assertNotEmpty($expires);
    $parsed = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $expires);
    $this->assertInstanceOf(\DateTimeImmutable::class, $parsed);
  }

  /**
   * After expiry, HEAD returns 404 or 410.
   */
  public function testExpiredUploadIsRejected(): void {
    $grant = $this->beginSessionKnownLength(10);
    $path = "/api/tus/$grant->uploadToken";
    $this->requestTime->setRequestTime($grant->session->uploadExpiresAt->getTimestamp() + 120);
    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertContains($response->getStatusCode(), [Response::HTTP_NOT_FOUND, Response::HTTP_GONE]);
    $this->assertTusHeaders($response);
  }

  /**
   * After expiry and cleanup, staged bytes are removed and tokens are invalid.
   */
  public function testCleanupRemovesStagedData(): void {
    $grant = $this->beginSessionKnownLength(10);
    $path = "/api/tus/$grant->uploadToken";
    $artifactUri = $grant->session->artifactUri;

    $this->tusRequest('PATCH', $path, 'aa', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertUriFileExists($artifactUri);

    // Advance time beyond expiry deadline.
    $this->requestTime->setRequestTime($grant->session->uploadExpiresAt->getTimestamp() + 1);

    // We only care that the module properly implements a cron hook for clean-up
    // we don't need to run cron entirely.
    $this->container->get('module_handler')->invoke('signed_file_upload', 'cron');

    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_GONE, $response->getStatusCode());
    $this->assertUriFileDoesNotExist($artifactUri);
  }

}
