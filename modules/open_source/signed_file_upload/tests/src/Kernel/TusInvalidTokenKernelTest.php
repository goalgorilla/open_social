<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\signed_file_upload\Enum\TokenType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tus requests with unknown, wrong-type, or malformed upload tokens.
 *
 * @group signed_file_upload
 */
class TusInvalidTokenKernelTest extends SignedFileUploadWithEntityDestinationTestBase {

  /**
   * Well-formed upload token that does not match any session: 404.
   */
  public function testUnknownUploadTokenHeadReturnsNotFound(): void {
    $token = TokenType::Upload->value . bin2hex(random_bytes(32));
    $path = "/api/tus/$token";
    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    $this->assertTusHeaders($response);
  }

  /**
   * Finalization token on the tus URL is not an upload token: 404.
   */
  public function testFinalizationTokenOnUploadUrlReturnsNotFound(): void {
    $token = TokenType::Finalization->value . bin2hex(random_bytes(32));
    $path = "/api/tus/$token";
    $response = $this->tusRequest('HEAD', $path, '', [
      'Tus-Resumable' => '1.0.0',
    ]);
    $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    $this->assertTusHeaders($response);
  }

  /**
   * Malformed token (not a recognized upload prefix): 404.
   */
  public function testMalformedTokenReturnsNotFound(): void {
    $token = 'zzz-not-an-upload-token';
    $path = "/api/tus/$token";
    $response = $this->tusRequest('PATCH', $path, 'x', [
      'Tus-Resumable' => '1.0.0',
      'Content-Type' => 'application/offset+octet-stream',
      'Upload-Offset' => '0',
    ]);
    $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    $this->assertTusHeaders($response);
  }

}
