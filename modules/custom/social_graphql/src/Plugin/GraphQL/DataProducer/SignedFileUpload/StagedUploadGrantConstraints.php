<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;

/**
 * Resolves resolved upload constraints from a staged upload grant.
 *
 * @DataProducer(
 *   id = "staged_upload_grant_constraints",
 *   name = @Translation("Staged upload grant constraints"),
 *   description = @Translation("Returns the constraint snapshot from the grant's upload session."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Resolved upload constraints"),
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
class StagedUploadGrantConstraints extends DataProducerPluginBase {

  /**
   * Returns the constraint snapshot stored on the upload session.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant $grant
   *   The staged upload grant wrapper (GraphQL parent value).
   *
   * @return \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints
   *   Normalized constraints for this upload.
   */
  public function resolve(StagedUploadGrant $grant): ResolvedUploadConstraints {
    return $grant->sessionGrant->session->constraints;
  }

}
