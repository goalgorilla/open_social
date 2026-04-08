<?php

declare(strict_types=1);

namespace Drupal\social_graphql;

use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\signed_file_upload\DataObject\UploadDestinationInterface;
use Drupal\signed_file_upload\SignedUploadFileLifecycleInterface;
use Drupal\social_graphql\Wrappers\FileInput;

/**
 * Resolves GraphQL FileInput values into managed file entities.
 */
class FileInputHandler {

  /**
   * Constructs a FileInputHandler.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user (actor for upload finalization).
   * @param \Drupal\signed_file_upload\SignedUploadFileLifecycleInterface|null $uploadFileLifecycle
   *   Finalizes staged uploads into files.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected ?SignedUploadFileLifecycleInterface $uploadFileLifecycle = NULL,
  ) {
  }

  /**
   * Converts file input to a file entity when applicable.
   *
   * @param \Drupal\social_graphql\Wrappers\FileInput|null $input
   *   GraphQL file input, or NULL when omitted.
   * @param \Drupal\signed_file_upload\DataObject\UploadDestinationInterface $destination
   *   Where the file should be attached.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file entity, or NULL when input was NULL.
   */
  public function inputToFile(?FileInput $input, UploadDestinationInterface $destination): ?FileInterface {
    if ($input === NULL) {
      return NULL;
    }

    // This can be removed once the signed_file_upload module is enabled
    // everywhere and the server is available. However, it needs to be optional
    // for the initial deploy to get around a container build order issue.
    if ($this->uploadFileLifecycle === NULL) {
      throw new \RuntimeException("Must enable signed_file_upload module.");
    }

    if ($input->isStagedUpload()) {
      return $this->uploadFileLifecycle->finalizeUpload(
        $input->stagedUpload,
        $this->currentUser,
        $destination,
      );
    }

    throw new \RuntimeException("support for upload method not implemented.");
  }

}
