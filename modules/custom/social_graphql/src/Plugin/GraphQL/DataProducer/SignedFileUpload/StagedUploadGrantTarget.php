<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;

/**
 * Resolves the StagedUploadTarget enum value requested for this grant.
 *
 * @DataProducer(
 *   id = "staged_upload_grant_target",
 *   name = @Translation("Staged upload grant target"),
 *   description = @Translation("Returns the client-requested StagedUploadTarget enum value."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Staged upload target"),
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
class StagedUploadGrantTarget extends DataProducerPluginBase {

  /**
   * Returns the GraphQL enum name for the upload target.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant $grant
   *   The staged upload grant wrapper (GraphQL parent value).
   *
   * @return string
   *   The StagedUploadTarget enum value string from the original request.
   */
  public function resolve(StagedUploadGrant $grant): string {
    return $grant->target;
  }

}
