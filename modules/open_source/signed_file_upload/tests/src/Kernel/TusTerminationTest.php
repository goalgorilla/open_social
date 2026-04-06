<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\SignedUploadFileLifecycleInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tus Termination: DELETE; staged resource cleanup.
 *
 * @group signed_file_upload
 */
class TusTerminationTest extends SignedFileUploadWithEntityDestinationTestBase {

  /**
   * Generic file field for tests that do not exercise image validation.
   */
  private EntityFieldUploadDestination $terminationFileDestination;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->terminationFileDestination = $this->installFileField('field_tus_termination_txt');
  }

  /**
   * Test that OPTIONS response advertises the termination extension.
   */
  public function testOptionsAdvertisesTerminationCapability(): void {
    $path = "/api/tus";
    $response = $this->tusRequest('OPTIONS', $path);
    $this->assertTusHeaders($response);
    $ext = array_map('trim', explode(',', $response->headers->get('Tus-Extension', '') ?? ''));
    $this->assertContains('termination', $ext);
  }

  /**
   * DELETE returns 204; staged file removed; HEAD gone; token invalid.
   */
  public function testDeleteReturnsNoContentAndResourceGone(): void {
    $grant = $this->beginSessionForDestination($this->terminationFileDestination, 'chunk.txt', 10);
    $path = "/api/tus/$grant->uploadToken";
    $artifactUri = $grant->session->artifactUri;

    $this->tusRequest('PATCH', $path, str_repeat('d', 10), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertUriFileExists($artifactUri);

    $delete = $this->tusRequest('DELETE', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $delete->getStatusCode());
    $this->assertTusHeaders($delete);

    $this->assertUriFileDoesNotExist($artifactUri);

    $head = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_GONE, $head->getStatusCode());
    $this->assertTusHeaders($head);
  }

  /**
   * After finalization, tus byte operations report Gone.
   */
  public function testDeleteAfterFinalizeReturnsGone(): void {
    $this->installEntitySchema('file');

    $payload = str_repeat('s', 256);
    $grant = $this->beginSessionForDestination($this->terminationFileDestination, 'final.txt', strlen($payload));
    $path = "/api/tus/$grant->uploadToken";

    $this->tusRequest('PATCH', $path, $payload, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);

    $lifecycle = $this->container->get(SignedUploadFileLifecycleInterface::class);
    $lifecycle->finalizeUpload(
      $grant->finalizationToken,
      $this->account(),
      $this->terminationFileDestination,
    );

    $delete = $this->tusRequest('DELETE', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_GONE, $delete->getStatusCode());
  }

  /**
   * DELETE on a locked file returns 409 conflict.
   */
  public function testDeleteDoesNothingOnConflict(): void {
    $grant = $this->beginSessionForDestination($this->terminationFileDestination, 'chunk.txt', 10);
    $path = "/api/tus/$grant->uploadToken";
    $artifactUri = $grant->session->artifactUri;

    $this->tusRequest('PATCH', $path, str_repeat('d', 10), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertUriFileExists($artifactUri);

    $this->simulateLockedFile($grant->session);

    $delete = $this->tusRequest('DELETE', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_CONFLICT, $delete->getStatusCode());
    $this->assertTusResumableHeader($delete);

    $this->assertUriFileExists($artifactUri);
  }

}
