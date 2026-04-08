<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;

/**
 * Resolves the filename from a staged upload grant.
 *
 * @DataProducer(
 *   id = "staged_upload_grant_filename",
 *   name = @Translation("Staged upload grant filename"),
 *   description = @Translation("Returns the filename from the grant's upload session metadata."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Filename"),
 *     required = TRUE
 *   ),
 *   consumes = {
 *     "grant" = @ContextDefinition("any",
 *       label = @Translation("Staged upload grant"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class StagedUploadGrantFilename extends DataProducerPluginBase {

  /**
   * Returns the client-provided filename from the session metadata.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant $grant
   *   The staged upload grant wrapper (GraphQL parent value).
   *
   * @return string
   *   The filename for the staged upload.
   */
  public function resolve(StagedUploadGrant $grant): string {
    return $grant->sessionGrant->session->metadata->filename;
  }

}
