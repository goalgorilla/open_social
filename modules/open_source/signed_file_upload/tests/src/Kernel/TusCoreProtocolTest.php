<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\DataObject\UploadSessionGrant;
use Drupal\signed_file_upload\Exception\InvalidContentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tus Core over HTTP: OPTIONS, HEAD, PATCH; Tus-Resumable preconditions.
 *
 * @group signed_file_upload
 */
class TusCoreProtocolTest extends SignedFileUploadWithEntityDestinationTestBase {

  /**
   * Plain-text file field for protocol tests that do not need images.
   */
  private EntityFieldUploadDestination $protocolTextDestination;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->protocolTextDestination = $this->installFileField('field_tus_core_protocol_txt');
  }

  /**
   * Known-length session on the shared protocol text file destination (.txt).
   *
   * @param non-negative-int $length
   *   The upload length to declare for the session.
   *
   * @return \Drupal\signed_file_upload\DataObject\UploadSessionGrant
   *   The created upload session grant.
   */
  protected function beginProtocolTextSessionKnownLength(int $length): UploadSessionGrant {
    return $this->beginSessionForDestination($this->protocolTextDestination, 'test.txt', $length);
  }

  /**
   * Defer-length session on the shared protocol text file destination (.txt).
   */
  protected function beginProtocolTextSessionDeferLength(): UploadSessionGrant {
    return $this->manager()->beginUploadSession(
      $this->protocolTextDestination,
      $this->account(),
      new UploadMetadata(filename: 'test.txt'),
      NULL,
    );
  }

  /**
   * OPTIONS: shows what the tus server can do.
   */
  public function testOptionsAdvertisesCapabilities(): void {
    $path = "/api/tus";
    $response = $this->tusRequest('OPTIONS', $path);
    $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NO_CONTENT]);
    $this->assertTusHeaders($response);
  }

  /**
   * HEAD: request responds with max length and current offset.
   */
  public function testHeadRespondsWithOffsetAndLengthOrDefer(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $this->assertSame('0', $response->headers->get('Upload-Offset'));
    $this->assertSame('3', $response->headers->get('Upload-Length'));
    $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control') ?? '');
  }

  /**
   * HEAD: indicates that upload-length has been deferred.
   */
  public function testHeadDeferLengthUsesUploadDeferLength(): void {
    $grant = $this->beginProtocolTextSessionDeferLength();
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $this->assertSame('0', $response->headers->get('Upload-Offset'));
    $this->assertNull($response->headers->get('Upload-Length'));
    $this->assertSame('1', $response->headers->get('Upload-Defer-Length'));
  }

  /**
   * PATCH: wrong Content-Type returns 415.
   */
  public function testPatchWrongContentTypeReturnsUnsupportedMediaType(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, 'abc', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'text/plain',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, $response->getStatusCode());
    $this->assertTusResumableHeader($response);
  }

  /**
   * PATCH: offset mismatch returns 409.
   */
  public function testPatchOffsetMismatchReturnsConflict(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, 'abc', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '99',
    ]);
    $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    $this->assertTusResumableHeader($response);
  }

  /**
   * PATCH: missing Upload-Offset returns 400 (Bad Request).
   */
  public function testPatchMissingUploadOffsetReturnsBadRequest(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, 'abc', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
    ]);
    $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    $this->assertTusResumableHeader($response);
  }

  /**
   * PATCH: payload larger than remaining should be rejected (409 Conflict).
   *
   * Policy: offset + body length must not exceed Upload-Length once known.
   */
  public function testPatchOversizedPayloadReturnsConflict(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, str_repeat('x', 4), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    $this->assertTusResumableHeader($response);
  }

  /**
   * Sequential PATCH: second chunk at wrong offset causes 409 Conflict.
   */
  public function testSecondPatchAtStaleOffsetReturnsConflict(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(10);
    $path = "/api/tus/$grant->uploadToken";
    $first = $this->tusRequest('PATCH', $path, str_repeat('a', 4), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $first->getStatusCode());
    $this->assertSame('4', $first->headers->get('Upload-Offset'));

    $second = $this->tusRequest('PATCH', $path, 'zzzzzzzz', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_CONFLICT, $second->getStatusCode());
    $this->assertTusResumableHeader($second);
  }

  /**
   * Two PATCH requests complete known-length upload; HEAD reflects full offset.
   */
  public function testMultiPatchCompletesUploadAndHeadShowsFullOffset(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(10);
    $path = "/api/tus/$grant->uploadToken";
    $first = $this->tusRequest('PATCH', $path, str_repeat('q', 5), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $first->getStatusCode());
    $this->assertTusHeaders($first);
    $this->assertSame('5', $first->headers->get('Upload-Offset'));

    $second = $this->tusRequest('PATCH', $path, 'fghij', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '5',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $second->getStatusCode());
    $this->assertTusHeaders($second);
    $this->assertSame('10', $second->headers->get('Upload-Offset'));

    $head = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $head->getStatusCode());
    $this->assertTusHeaders($head);
    $this->assertSame('10', $head->headers->get('Upload-Offset'));
    $this->assertSame('10', $head->headers->get('Upload-Length'));
  }

  /**
   * Defer-length: first PATCH may send Upload-Length to fix total size.
   *
   * This corresponds to part of the tus creation-defer-length extension.
   */
  public function testDeferLengthFirstPatchWithUploadLengthFixesLength(): void {
    $grant = $this->beginProtocolTextSessionDeferLength();
    $path = "/api/tus/$grant->uploadToken";
    $patch = $this->tusRequest('PATCH', $path, 'hi', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
      'Upload-Length' => '10',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $patch->getStatusCode());
    $this->assertTusHeaders($patch);

    $head = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $head->getStatusCode());
    $this->assertTusHeaders($head);
    $this->assertSame('2', $head->headers->get('Upload-Offset'));
    $this->assertSame('10', $head->headers->get('Upload-Length'));
    $this->assertNull($head->headers->get('Upload-Defer-Length'));
  }

  /**
   * Zero-byte upload: HEAD shows complete state (offset 0, length 0).
   */
  public function testZeroByteUploadHeadShowsCompleteWithoutPatch(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(0);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $this->assertSame('0', $response->headers->get('Upload-Offset'));
    $this->assertSame('0', $response->headers->get('Upload-Length'));
  }

  /**
   * Missing or unsupported Tus-Resumable: 412 + Tus-Version on response.
   */
  public function testMissingTusResumableReturnsPreconditionFailed(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, 'abc', [
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());
    $this->assertSame('1.0.0', $response->headers->get('Tus-Version'));
    $this->assertTusResumableHeader($response);
  }

  /**
   * Successful PATCH: 204 and updated Upload-Offset.
   */
  public function testPatchSuccessReturnsNoContentWithNewOffset(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, str_repeat('y', 3), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $this->assertSame('3', $response->headers->get('Upload-Offset'));
    $this->assertNotEmpty($response->headers->get('Upload-Expires'));
  }

  /**
   * Patch on a locked file causes 409 Conflict.
   */
  public function testPatchOnLockedFileCausesConflict(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(3);
    $path = "/api/tus/$grant->uploadToken";

    $this->simulateLockedFile($grant->session);

    $response = $this->tusRequest('PATCH', $path, str_repeat('y', 3), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    $this->assertTusResumableHeader($response);
  }

  /**
   * PATCH: first bytes must match declared format (e.g. JPEG for .jpg).
   *
   * Uses the default image destination (`test.jpg`); plain `.txt` sessions skip
   * magic-byte validation.
   */
  public function testPatchWrongMagicBytesReturnsUnprocessableEntity(): void {
    $grant = $this->beginSessionKnownLength(20);
    $path = "/api/tus/$grant->uploadToken";
    $pngLike = "\x89PNG\r\n\x1A\n" . str_repeat('0', 12);
    $response = $this->tusRequest('PATCH', $path, $pngLike, [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    $this->assertTusResumableHeader($response);
  }

  /**
   * OPTIONS should advertise Tus-Max-Size when server enforces a hard limit.
   *
   * The value must be a non-negative integer (bytes). The implementation may
   * derive this from PHP limits, field settings, or site configuration.
   */
  public function testOptionsIncludesTusMaxSizeWhenHardLimitIsKnown(): void {
    $this->markTestSkipped("Module does not yet provide a way to set maximum upload size and getting the maximum upload from PHP makes no sense since it defeats the purpose of being able to upload in multiple requests.");
    // @phpstan-ignore-next-line deadCode.unreachable
    $response = $this->tusRequest('OPTIONS', '/api/tus');
    $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NO_CONTENT]);
    $max = $response->headers->get('Tus-Max-Size');
    $this->assertNotNull($max, 'OPTIONS should include Tus-Max-Size when a hard upload limit exists.');
    $this->assertMatchesRegularExpression('/^\d+$/', $max);
  }

  /**
   * HEAD includes Tus-Max-Size matching session's maximum upload size (bytes).
   */
  public function testHeadIncludesTusMaxSizeMatchingSessionLimit(): void {
    $destination = $this->installFileField('field_tus_max', ['max_filesize' => '1 kB']);
    $grant = $this->beginSessionForDestination($destination, 'tiny.txt', 100);
    $expected = $grant->session->constraints->maxBytes;
    $path = "/api/tus/$grant->uploadToken";

    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $this->assertTusMaxSize($response, $expected);
  }

  /**
   * Successful PATCH includes Tus-Max-Size matching the session limit.
   */
  public function testPatchSuccessIncludesTusMaxSizeMatchingSessionLimit(): void {
    $destination = $this->installFileField('field_tus_max_patch', ['max_filesize' => '1 kB']);
    $grant = $this->beginSessionForDestination($destination, 'tiny.txt', 10);
    $expected = $grant->session->constraints->maxBytes;
    $path = "/api/tus/$grant->uploadToken";

    $response = $this->tusRequest('PATCH', $path, str_repeat('p', 10), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertTusHeaders($response);
    $this->assertTusMaxSize($response, $expected);
  }

  /**
   * BeginUploadSession rejects upload length above field max file size.
   */
  public function testBeginUploadSessionRejectsLengthAboveFieldMaxFilesize(): void {
    $destination = $this->installFileField('field_size_begin', ['max_filesize' => '1 kB']);

    $this->expectException(InvalidContentException::class);
    $this->expectExceptionMessage('Requested upload length 10000 is larger than maximum for target 1024');
    $this->beginSessionForDestination($destination, 'x.txt', 10_000);
  }

  /**
   * PATCH rejects when the total upload exceed the session Upload-Length (409).
   *
   * Same class of error as exceeding the field max when those limits are
   * aligned.
   */
  public function testPatchRejectsChunkWhenTotalWouldExceedUploadLength(): void {
    $destination = $this->installFileField('field_size_patch', ['max_filesize' => '1 kB']);
    $grant = $this->beginSessionForDestination($destination, 'x.txt', 1024);
    $path = "/api/tus/$grant->uploadToken";

    $first = $this->tusRequest('PATCH', $path, str_repeat('a', 500), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $first->getStatusCode());

    $second = $this->tusRequest('PATCH', $path, str_repeat('b', 600), [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '500',
    ]);
    $this->assertSame(Response::HTTP_CONFLICT, $second->getStatusCode());
    $this->assertTusResumableHeader($second);
  }

  /**
   * Defer-length: PATCH must not set Upload-Length above the field max size.
   */
  public function testDeferLengthFirstPatchRejectsUploadLengthAboveFieldMaxFilesize(): void {
    $destination = $this->installFileField('field_size_defer', ['max_filesize' => '1 kB']);
    $grant = $this->beginSessionForDestination($destination, 'x.txt', NULL);
    $path = "/api/tus/$grant->uploadToken";

    $response = $this->tusRequest('PATCH', $path, 'x', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
      'Upload-Length' => '100000',
    ]);
    $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    $this->assertTusResumableHeader($response);
  }

  /**
   * PUT is not an allowed method on the tus upload URL (routing rejects it).
   */
  public function testPutToTusUploadResourceReturnsMethodNotAllowed(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(1);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PUT', $path, '');
    $this->assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    $allow = array_map('trim', explode(',', (string) $response->headers->get('Allow', '')));
    foreach (['HEAD', 'PATCH', 'DELETE'] as $method) {
      $this->assertContains($method, $allow, sprintf('Allow header "%s" should list %s.', $response->headers->get('Allow'), $method));
    }
  }

  /**
   * GET matches the route but controller only allows HEAD, PATCH, DELETE.
   */
  public function testGetToTusUploadResourceReturnsMethodNotAllowed(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(1);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('GET', $path, '');
    $this->assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    $allow = array_map('trim', explode(',', (string) $response->headers->get('Allow', '')));
    foreach (['HEAD', 'PATCH', 'DELETE'] as $method) {
      $this->assertContains($method, $allow, sprintf('Allow header "%s" should list %s.', $response->headers->get('Allow'), $method));
    }
  }

  /**
   * PATCH with Upload-Length when the session already has Upload-Length: 400.
   */
  public function testPatchSendsUploadLengthWhenLengthAlreadyKnownReturnsBadRequest(): void {
    $grant = $this->beginProtocolTextSessionKnownLength(10);
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, 'x', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
      'Upload-Length' => '10',
    ]);
    $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    $this->assertSame('Upload length was already set', $response->getContent());
    $this->assertTusResumableHeader($response);
  }

  /**
   * Defer-length: negative Upload-Length header returns 400.
   */
  public function testDeferLengthPatchWithNegativeUploadLengthReturnsBadRequest(): void {
    $grant = $this->beginProtocolTextSessionDeferLength();
    $path = "/api/tus/$grant->uploadToken";
    $response = $this->tusRequest('PATCH', $path, 'x', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
      'Upload-Length' => '-1',
    ]);
    $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    $this->assertSame('Upload length is invalid (must be 0 or greater).', $response->getContent());
    $this->assertTusResumableHeader($response);
  }

  /**
   * Defer-length: Upload-Length below bytes already received must be rejected.
   *
   * Without a guard, a client could PATCH Upload-Length smaller than the
   * current offset after data is on disk, persist offset > length, and
   * finalizeUpload() would treat the upload as complete incorrectly.
   */
  public function testDeferLengthPatchRejectsUploadLengthBelowCurrentOffset(): void {
    $grant = $this->beginProtocolTextSessionDeferLength();
    $path = "/api/tus/$grant->uploadToken";

    $first = $this->tusRequest('PATCH', $path, 'abcde', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $first->getStatusCode());
    $this->assertSame('5', $first->headers->get('Upload-Offset'));
    $this->assertNull($first->headers->get('Upload-Length'));

    $second = $this->tusRequest('PATCH', $path, '', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '5',
      'Upload-Length' => '3',
    ]);
    $this->assertSame(Response::HTTP_BAD_REQUEST, $second->getStatusCode());
    $this->assertSame('Upload length is invalid (passed Upload-Offset).', $second->getContent());

    $head = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NO_CONTENT, $head->getStatusCode());
    $this->assertSame('5', $head->headers->get('Upload-Offset'));
    $this->assertNull($head->headers->get('Upload-Length'));
    $this->assertSame('1', $head->headers->get('Upload-Defer-Length'));
  }

}
