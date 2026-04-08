<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant;

/**
 * Resolves whether upload length is deferred for a staged upload grant.
 *
 * @DataProducer(
 *   id = "staged_upload_grant_size_deferred",
 *   name = @Translation("Staged upload grant size deferred"),
 *   description = @Translation("Returns TRUE when the upload length is not yet known (tus defer-length)."),
 *   produces = @ContextDefinition("boolean",
 *     label = @Translation("Size deferred"),
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
class StagedUploadGrantSizeDeferred extends DataProducerPluginBase {

  /**
   * Returns whether the upload length is still deferred.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant $grant
   *   The staged upload grant wrapper (GraphQL parent value).
   *
   * @return bool
   *   TRUE when upload length is not yet known.
   */
  public function resolve(StagedUploadGrant $grant): bool {
    return $grant->sessionGrant->session->uploadLength === NULL;
  }

}
