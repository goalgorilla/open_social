<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;

/**
 * Resolves the file size from a staged upload grant when known.
 *
 * @DataProducer(
 *   id = "staged_upload_grant_file_size",
 *   name = @Translation("Staged upload grant file size"),
 *   description = @Translation("Returns the upload length from the grant's session, if set."),
 *   produces = @ContextDefinition("integer",
 *     label = @Translation("File size"),
 *     required = FALSE
 *   ),
 *   consumes = {
 *     "grant" = @ContextDefinition("any",
 *       label = @Translation("Staged upload grant"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class StagedUploadGrantFileSize extends DataProducerPluginBase {

  /**
   * Returns the total upload length when known, or NULL if deferred.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant $grant
   *   The staged upload grant wrapper (GraphQL parent value).
   *
   * @return int|null
   *   Byte length when set; NULL under tus defer-length.
   */
  public function resolve(StagedUploadGrant $grant): ?int {
    return $grant->sessionGrant->session->uploadLength;
  }

}
