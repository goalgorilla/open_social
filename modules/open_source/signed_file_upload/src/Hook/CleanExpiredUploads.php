<?php
// phpcs:ignoreFile -- until we update coder to handle attributes.

declare(strict_types=1);

namespace Drupal\signed_file_upload\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Site\Settings;
use Drupal\signed_file_upload\Entity\UploadSessionRecordStorage;

/**
 * Cron implementation to clean expired tus uploads.
 */
class CleanExpiredUploads {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
    protected FileSystemInterface $fileSystem,
  ) {
  }

  /**
   * The cron hook implementation.
   */
  // @phpstan-ignore-next-line
  #[Hook('cron')]
  public function cron() : void {
    /** @var \Drupal\signed_file_upload\Entity\UploadSessionRecord[] $expired_uploads */
    $expired_uploads = $this->getStorage()->loadUploadsRequireCleaning($this->time->getCurrentTime(), Settings::get('entity_update_batch_size', 50));
    foreach ($expired_uploads as $expired_upload) {
      $this->fileSystem->delete($expired_upload->getUploadSession()->artifactUri);
      $expired_upload->markExpired()->save();
    }
  }

  /**
   * Get the upload session record storage.
   *
   * @return \Drupal\signed_file_upload\Entity\UploadSessionRecordStorage
   *   The upload session record storage.
   */
  protected function getStorage() : UploadSessionRecordStorage {
    return $this->entityTypeManager->getStorage('signed_file_upload_session');
  }

}
