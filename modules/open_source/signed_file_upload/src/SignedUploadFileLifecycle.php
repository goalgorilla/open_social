<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Upload\FileUploadLocationTrait;
use Drupal\signed_file_upload\DataObject\PatchUploadResult;
use Drupal\signed_file_upload\DataObject\UploadDestinationInterface;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Drupal\signed_file_upload\Entity\UploadSessionRecordStorage;
use Drupal\signed_file_upload\Enum\PatchResult;
use Drupal\signed_file_upload\Enum\UploadState;
use Drupal\signed_file_upload\Exception\InvalidFinalizationDestinationException;
use Drupal\signed_file_upload\Exception\OperationConflictException;
use Drupal\signed_file_upload\Exception\UploadIncompleteException;

/**
 * Service to handle the lifecycle of the upload file given a valid session.
 */
class SignedUploadFileLifecycle implements SignedUploadFileLifecycleInterface {

  protected const string LOCK_NAME = "suf_upload_session_lock";

  use FileUploadLocationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly UploadConstraintResolverInterface $constraintResolver,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly UploadValidatorInterface $uploadValidator,
    protected readonly LockBackendInterface $lock,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function patchUpload(
    UploadSession $session,
    $input,
    int $uploadOffset,
    ?int $uploadLength,
  ): PatchUploadResult {
    $this->lockSession($session);

    try {
      // Load the record for bookkeeping and refresh the session since we can
      // only now be sure that we have a lock and the previous session may be
      // stale.
      $record = $this->getStorage()->loadForSession($session);
      $session = $record->getUploadSession();

      if ($uploadOffset !== $session->offset) {
        return new PatchUploadResult(
          result: PatchResult::UploadOffsetMismatch,
          session: $session,
        );
      }

      // If we have an upload length, disallow the client from attempting to
      // change it and use it.
      if ($session->uploadLength !== NULL) {
        if ($uploadLength !== NULL) {
          return new PatchUploadResult(
            result: PatchResult::UploadLengthAlreadySet,
            session: $session,
          );
        }

        $uploadLength = $session->uploadLength;
      }
      // If we don't have one but the client provided one, then we want to
      // update it so it's persisted for our session.
      elseif ($uploadLength !== NULL) {
        if ($uploadLength < 0) {
          return new PatchUploadResult(
            result: PatchResult::NegativeUploadLength,
            session: $session,
          );
        }
        if ($uploadLength < $session->offset) {
          return new PatchUploadResult(
            result: PatchResult::PassedUploadLength,
            session: $session
          );
        }
        if ($session->constraints->maxBytes > 0 && $uploadLength > $session->constraints->maxBytes) {
          return new PatchUploadResult(
            result: PatchResult::TooLargeUploadLength,
            session: $session,
          );
        }

        $record->setUploadLength($uploadLength);
      }

      $target_file = fopen($session->artifactUri, 'ab');

      if ($target_file === FALSE) {
        throw new \RuntimeException("Could not open '$session->artifactUri' for writing in TusUpload.");
      }

      $max_bytes = $uploadLength !== NULL
        ? $uploadLength - $session->offset
        // @todo This should be configurable to a maximum server size.
        // @todo Also for any non-zero offset, PHP_INT_MAX would cause an overflow.
        : PHP_INT_MAX;

      // We ignore any output from PHPs stream copying since the tus
      // specification tells us to process as much as possible. A stream error
      // (which would return FALSE) is an expected scenario and we later inspect
      // the file on disk to check what our new offset should be.
      stream_copy_to_stream($input, $target_file, $max_bytes);

      // If we can still read from the input and receive non-empty contents,
      // then stream_copy_to_stream stopped because it hit max bytes. That means
      // that  the client tried to send us too much, which is a client error.
      $overflow = fread($input, 1);
      $exceeded_length = $overflow !== FALSE && $overflow !== '';
      $end_offset = ftell($target_file);

      fclose($target_file);

      if ($end_offset === FALSE) {
        throw new \RuntimeException("Failed to determine end offset of file '$session->artifactUri' for upload session $session->sessionId.");
      }

      if (!$this->uploadValidator->validateArtifactContentSignature($session, $end_offset)) {
        $this->resetStagedArtifact($session->artifactUri);
        $record->setUploadOffset(0);
        $record->save();

        return new PatchUploadResult(
          result: PatchResult::InvalidContentSignature,
          session: $record->getUploadSession(),
        );
      }

      $record->setUploadOffset($end_offset);
      $record->save();

      return new PatchUploadResult(
        result: $exceeded_length ? PatchResult::LengthExceeded : PatchResult::Ok,
        session: $record->getUploadSession(),
      );
    }
    finally {
      $this->unlockSession($session);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUpload(UploadSession $session): void {
    $this->lockSession($session);
    try {
      // Load the record for bookkeeping and refresh the session since we can
      // only now be sure that we have a lock and the previous session may be
      // stale.
      $record = $this->getStorage()->loadForSession($session);
      $session = $record->getUploadSession();

      if ($session->state !== UploadState::Uploading) {
        throw new \InvalidArgumentException('Only an in-progress upload session can be deleted.');
      }

      // We're fine with trying to delete things multiple times as these
      // operations are idempotent.
      $this->fileSystem->delete($session->artifactUri);
      $record
        ->markTerminated()
        ->save();
    }
    finally {
      $this->unlockSession($session);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finalizeUpload(
    string $finalizationToken,
    AccountInterface $account,
    UploadDestinationInterface $expectedDestination,
  ): FileInterface {
    $record = $this->getStorage()->loadByFinalizationToken($finalizationToken);

    if ($record === NULL) {
      throw new \InvalidArgumentException("Invalid finalization token.");
    }

    $session = $record->getUploadSession();
    $this->lockSession($session);

    try {
      if ($session->uploadLength !== NULL && $session->offset < $session->uploadLength) {
        throw new UploadIncompleteException($session->uploadLength, $session->offset);
      }

      $destination = $record->getDestination();
      if (!$expectedDestination->equals($destination)) {
        // @todo add details about what?
        throw new InvalidFinalizationDestinationException();
      }

      $constraints = $this->constraintResolver->resolve($destination);

      $this->uploadValidator->assertStagedFile($constraints, $session);

      $directory_uri = $constraints->targetDirectory;
      if (
        !$this->fileSystem->prepareDirectory(
          $directory_uri,
          FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
        )
      ) {
        throw new \RuntimeException(sprintf('Could not prepare directory %s.', $directory_uri));
      }

      $filepath = $this->fileSystem->move($session->artifactUri, "$directory_uri/{$session->metadata->filename}");

      $file = File::create([
        'uid' => $account->id(),
        'uri' => $filepath,
        'status' => 0,
      ]);
      $file->save();

      $record
        ->markFinalized()
        ->save();

      return $file;
    }
    finally {
      $this->unlockSession($session);
    }
  }

  /**
   * Clears staged bytes after a failed signature check.
   */
  protected function resetStagedArtifact(string $uri): void {
    $this->fileSystem->delete($uri);
    $this->fileSystem->saveData('', $uri, FileExists::Replace);
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

  /**
   * Ensure that the operation executed is the only one.
   *
   * Avoids trying to perform multiple operations on the same file at the same
   * time.
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The session to lock.
   *
   * @throws \Drupal\signed_file_upload\Exception\OperationConflictException
   *   In case the file for the session is already in use and the lock cannot
   *   be acquired.
   */
  protected function lockSession(UploadSession $session) : void {
    $lock = self::LOCK_NAME . '.' . $session->sessionId;
    if (!$this->lock->acquire($lock)) {
      throw new OperationConflictException("Lock for $session->sessionId could not be acquired.");
    }
  }

  /**
   * Unlock the session.
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The session to unlock.
   */
  protected function unlockSession(UploadSession $session) : void {
    $lock = self::LOCK_NAME . '.' . $session->sessionId;
    $this->lock->release($lock);
  }

}
