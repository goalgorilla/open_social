<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\signed_file_upload\UploadSessionManager;

/**
 * Staged artifact URIs must not be downloadable via private file HTTP.
 *
 * @group signed_file_upload
 */
class PrivateStagedFileDownloadKernelTest extends SignedFileUploadTestBase {

  /**
   * Hook_file_download denies access to files under the in-progress directory.
   */
  public function testHookFileDownloadDeniesStagedArtifactUri(): void {
    $uri = 'private://' . UploadSessionManager::IN_PROGRESS_DIRECTORY . '/test-staged-artifact.part';
    // Invoke only this module: invokeAll() would run file_file_download(),
    // which needs a full file/user stack; we only assert
    // signed_file_upload_file_download() denies staged URIs.
    $result = \Drupal::moduleHandler()->invoke('signed_file_upload', 'file_download', [$uri]);
    $this->assertSame(-1, $result);
  }

}
