<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\ImageDimension;
use Drupal\signed_file_upload\DataObject\ImageDimensionBounds;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Drupal\signed_file_upload\Enum\UploadState;
use Drupal\signed_file_upload\Enum\UploadValidationKind;

/**
 * Defines the upload session entity for signed tus-aligned uploads.
 *
 * @ContentEntityType(
 *   id = "signed_file_upload_session",
 *   label = @Translation("Upload session"),
 *   label_collection = @Translation("Upload sessions"),
 *   label_singular = @Translation("upload session"),
 *   label_plural = @Translation("upload sessions"),
 *   handlers = {
 *     "storage" = "Drupal\signed_file_upload\Entity\UploadSessionRecordStorage",
 *     "storage_schema" = "Drupal\signed_file_upload\Entity\UploadSessionRecordStorageSchema",
 *   },
 *   base_table = "upload_session",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "uuid" = "uuid",
 *   },
 * )
 */
final class UploadSessionRecord extends ContentEntityBase implements UploadSessionRecordInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['upload_token_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Upload token hash'))
      ->setDescription(t('One-way sha-256 hash of the token for lookup/validation.'))
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setSetting('max_length', 64);

    $fields['finalization_token_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Finalization token hash'))
      ->setDescription(t('One-way sha-256 hash of the token for lookup/validation.'))
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setSetting('max_length', 64);

    $fields['upload_offset'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Upload offset'))
      ->setDescription(t('Current byte offset for the next PATCH (tus Upload-Offset).'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big');

    $fields['total_length'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total length'))
      ->setDescription(t('Total upload length when known; empty while defer-length is pending.'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big');

    $fields['state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State'))
      ->setDescription(t('The state of the upload, should be one of UploadRecordState.'))
      ->setDefaultValue(UploadState::Uploading->value);

    $fields['upload_expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Upload expires'))
      ->setDescription(t('When the unfinished upload expires (Upload-Expires semantics).'));

    $fields['staged_artifact_uri'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Staged artifact URI'))
      ->setDescription(t('Reference to staged bytes (e.g. temp URI).'))
      ->setSetting('max_length', 2048);

    $fields['uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Owner user ID'))
      ->setDescription(t('The user id that started the session (0 for anonymous).'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    $fields['destination'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Destination'))
      ->setDescription(t('Serialized EntityFieldUploadDestination or EditorUploadDestination.'));

    $fields['resolved_constraints'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resolved constraints'))
      ->setDescription(t('Serialized ResolvedUploadConstraints snapshot at creation.'));

    $fields['upload_metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Upload metadata'))
      ->setDescription(t('Serialized UploadMetadata snapshot at creation.'));

    return $fields;
  }

  /**
   * Get the entity ID.
   *
   * @return null|non-negative-int
   *   The entity ID.
   */
  public function id() {
    $id = parent::id();
    $id = $id === NULL ? NULL : (int) $id;
    assert($id === NULL || $id >= 0);
    return $id;
  }

  /**
   * Create e new upload session record.
   *
   * Convenience function to avoid having to guess at all the field names in an
   * array.
   *
   * @param string $uploadToken
   *   The raw upload token (will be hashed before storage).
   * @param string $finalizationToken
   *   The raw finalization token (will be hashedb efore storage).
   * @param \Drupal\signed_file_upload\DataObject\EditorUploadDestination|\Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination $destination
   *   The upload destination.
   * @param int $offset
   *   The initial offset in case a (partial) file was included in the beginning
   *   of the upload.
   * @param int|null $uploadLength
   *   The client indicated upload length or NULL if the length is deferred.
   * @param \DateTimeImmutable $uploadExpiresAt
   *   The expiration date for the upload session.
   * @param string $artifactUri
   *   The temporary artifact URI on disk where the upload is assembled.
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   The resolved constraints for the upload based on the destination.
   * @param \Drupal\signed_file_upload\DataObject\UploadMetadata $metadata
   *   The upload metadata supplied by the client.
   * @param \Drupal\Core\Session\AccountInterface $uploader
   *   The account that's responsible for the upload.
   *
   * @return static
   *   A new UploadSessionRecord instance.
   */
  public static function createRecord(
    string $uploadToken,
    string $finalizationToken,
    EditorUploadDestination|EntityFieldUploadDestination $destination,
    int $offset,
    ?int $uploadLength,
    \DateTimeImmutable $uploadExpiresAt,
    string $artifactUri,
    ResolvedUploadConstraints $constraints,
    UploadMetadata $metadata,
    AccountInterface $uploader,
  ) : static {
    return UploadSessionRecord::create([
      'upload_token_hash' => self::hashToken($uploadToken),
      'finalization_token_hash' => self::hashToken($finalizationToken),
      'upload_offset' => $offset,
      'total_length' => $uploadLength,
      'upload_expires' => $uploadExpiresAt->getTimestamp(),
      'staged_artifact_uri' => $artifactUri,
      'uid' => $uploader->id(),
      'destination' => serialize($destination),
      'resolved_constraints' => serialize($constraints),
      'upload_metadata' => serialize($metadata),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadSession(): UploadSession {
    $id = $this->id();
    assert($id !== NULL, "getUploadSession should not be called on unsaved records.");
    $length = $this->total_length->value !== NULL ? (int) $this->total_length->value : NULL;
    assert($length === NULL || $length >= 0, "UploadSessionRecord.total_length must be a non-negative integer or null.");
    $offset = (int) $this->upload_offset->value;
    assert($offset >= 0, "UploadSessionRecord.upload_offset must be unsigned integer.");
    return new UploadSession(
      sessionId: $id,
      state: UploadState::from($this->state->value),
      offset: $offset,
      uploadLength: $length,
      uploadExpiresAt: (new \DateTimeImmutable())->setTimestamp((int) $this->upload_expires->value),
      artifactUri: $this->staged_artifact_uri->value,
      constraints: unserialize(
        $this->resolved_constraints->value,
        [
          'allowed_classes' => [
            ResolvedUploadConstraints::class,
            UploadValidationKind::class,
            ImageDimensionBounds::class,
            ImageDimension::class,
          ],
        ],
      ),
      metadata: unserialize($this->upload_metadata->value, ['allowed_classes' => [UploadMetadata::class]]),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUploadOffset(int $offset): UploadSessionRecordInterface {
    return $this->set('upload_offset', $offset);
  }

  /**
   * {@inheritdoc}
   */
  public function setUploadLength(int $length): UploadSessionRecordInterface {
    return $this->set('total_length', $length);
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination(): EntityFieldUploadDestination|EditorUploadDestination {
    return unserialize(
      $this->destination->value,
      [
        'allowed_classes' => [
          EntityFieldUploadDestination::class,
          EditorUploadDestination::class,
        ],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function markTerminated() : self {
    return $this->set('state', UploadState::Terminated->value);
  }

  /**
   * {@inheritdoc}
   */
  public function markExpired() : self {
    return $this->set('state', UploadState::Expired->value);
  }

  /**
   * {@inheritdoc}
   */
  public function markFinalized() : self {
    return $this->set('state', UploadState::Finalized->value);
  }

  /**
   * Hash a token for storage in the database.
   *
   * @todo This should not be a public method, refactor the usage in storage.
   *
   * @param string $token
   *   The token.
   *
   * @return string
   *   The hash.
   */
  public static function hashToken(string $token): string {
    // Given the high entropy of our tokens, it is secure to hash without salt.
    return hash('sha256', $token);
  }

}
