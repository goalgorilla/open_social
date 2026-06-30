<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Drupal\signed_file_upload\DataObject\UploadSessionGrant;
use Drupal\signed_file_upload\Entity\UploadSessionRecord;
use Drupal\signed_file_upload\Enum\TokenType;

/**
 * The Upload Session Manager.
 *
 * Responsible for the issuing and later retrieval of upload sessions.
 */
class UploadSessionManager implements UploadSessionManagerInterface {

  /**
   * The length of a token in bytes.
   */
  const int TOKEN_BYTES_LENGTH = 64;

  /**
   * Relative directory under private:// for staged (incomplete) upload bytes.
   */
  public const string IN_PROGRESS_DIRECTORY = 'signed_file_upload';

  public function __construct(
    protected readonly UploadConstraintResolverInterface $constraintResolver,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly TimeInterface $time,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly UploadValidatorInterface $uploadValidator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function beginUploadSession(
    EntityFieldUploadDestination|EditorUploadDestination $destination,
    AccountInterface $account,
    UploadMetadata $metadata,
    ?int $uploadLength = NULL,
  ): UploadSessionGrant {
    $constraints = $this->constraintResolver->resolve($destination);

    $this->uploadValidator->assertUploadIntent($constraints, $metadata, $uploadLength);

    $uploadToken = $this->generateToken(TokenType::Upload);
    $finalizeToken = $this->generateToken(TokenType::Finalization);
    $expirationTime = $this->getExpirationTime();
    $stagedUrl = $this->getStagedArtifactUri();

    $record = UploadSessionRecord::createRecord(
      uploadToken: $uploadToken,
      finalizationToken: $finalizeToken,
      destination: $destination,
      offset: 0,
      uploadLength: $uploadLength,
      uploadExpiresAt: $expirationTime,
      artifactUri: $stagedUrl,
      constraints: $constraints,
      metadata: $metadata,
      uploader: $account,
    );

    if ($record->save() !== SAVED_NEW) {
      throw new \RuntimeException("Unable to store new session in the database.");
    }

    return new UploadSessionGrant(
      uploadToken: $uploadToken,
      finalizationToken: $finalizeToken,
      destination: $destination,
      session: $record->getUploadSession()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadSession(string $token) : ?UploadSession {
    if (!$this->isTokenType($token, TokenType::Upload)) {
      return NULL;
    }
    return $this->entityTypeManager
      ->getStorage('signed_file_upload_session')
      ->loadByUploadToken($token)
      ?->getUploadSession();
  }

  /**
   * Generate a token.
   *
   * A token consists of self::TOKEN_BYTES_LENGTH random bytes encoded as
   * base64, prefixed with the token type for identification.
   *
   * @param \Drupal\signed_file_upload\Enum\TokenType $type
   *   The type of token to generate.
   *
   * @return non-empty-string
   *   The generated token.
   */
  protected function generateToken(TokenType $type): string {
    $random = Crypt::randomBytesBase64(self::TOKEN_BYTES_LENGTH);
    return $type->value . $random;
  }

  /**
   * Check whether the token is of the correct type.
   *
   * This can help prevent users from incorrectly using tokens.
   *
   * @param non-empty-string $token
   *   The token.
   * @param \Drupal\signed_file_upload\Enum\TokenType $expectedType
   *   The type we expect the token to be.
   *
   * @return bool
   *   Whether the token is of the expected type.
   */
  protected function isTokenType(string $token, TokenType $expectedType): bool {
    return str_starts_with($token, $expectedType->value);
  }

  /**
   * Get the expiration time for a new session.
   *
   * @return \DateTimeImmutable
   *   The expiration date.
   */
  protected function getExpirationTime() : \DateTimeImmutable {
    // Keep only the if-clause in PHP 8.4 or later.
    if (PHP_VERSION_ID >= 80400) {
      $date = \DateTimeImmutable::createFromTimestamp(
        $this->time->getRequestTime()
      );
    }
    else {
      $date = (new \DateTimeImmutable())->setTimestamp(
        $this->time->getRequestTime()
      );
    }
    $expiry_time = $this->configFactory->get('signed_file_upload.settings')
      ->get('expiry_time');
    if (!is_string($expiry_time) || $expiry_time === '') {
      throw new \RuntimeException(sprintf('Invalid signed_file_upload.settings:expiry_time value %s; expected a non-empty string.', var_export($expiry_time, TRUE)));
    }
    $interval = \DateInterval::createFromDateString($expiry_time);
    if ($interval === FALSE) {
      throw new \RuntimeException(sprintf('Invalid signed_file_upload.settings:expiry_time value "%s".', $expiry_time));
    }
    return $date->add($interval);
  }

  /**
   * Generates and returns the URI for a staged artifact file.
   *
   * This method ensures that the necessary directory for storing the staged
   * artifact exists and has proper permissions. If the directory cannot be
   * created or modified, an exception is thrown. A unique filename is then
   * generated for the artifact within the directory.
   *
   * @return non-empty-string
   *   The URI of the staged artifact file in the designated directory.
   *
   * @throws \RuntimeException
   *   Thrown if the signed file upload directory cannot be created or modified.
   */
  protected function getStagedArtifactUri() : string {
    $directory_uri = 'private://' . self::IN_PROGRESS_DIRECTORY;
    if (
      !$this->fileSystem->prepareDirectory(
        $directory_uri,
        FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
      )
    ) {
      throw new \RuntimeException(
        sprintf('Unable to create or write to the signed file upload directory %s.', $directory_uri)
      );
    }

    $filename = bin2hex(random_bytes(16)) . '.part';
    return $directory_uri . '/' . $filename;
  }

}
