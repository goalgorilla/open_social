<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;

/**
 * Resolves the finalization token from a staged upload grant.
 *
 * @DataProducer(
 *   id = "staged_upload_grant_finalization_token",
 *   name = @Translation("Staged upload grant finalization token"),
 *   description = @Translation("Returns the secret token used to finalize the upload."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Finalization token"),
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
class StagedUploadGrantFinalizationToken extends DataProducerPluginBase {

  /**
   * Returns the secret used to finalize the upload via OAuth-backed APIs.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant $grant
   *   The staged upload grant wrapper (GraphQL parent value).
   *
   * @return string
   *   The finalization token.
   */
  public function resolve(StagedUploadGrant $grant): string {
    return $grant->sessionGrant->finalizationToken;
  }

}
