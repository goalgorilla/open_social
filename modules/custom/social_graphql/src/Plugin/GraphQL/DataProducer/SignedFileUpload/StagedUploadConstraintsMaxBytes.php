<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;

/**
 * Resolves max file size from resolved upload constraints.
 *
 * @DataProducer(
 *   id = "staged_upload_constraints_max_bytes",
 *   name = @Translation("Staged upload constraints max bytes"),
 *   description = @Translation("Returns the maximum file size in bytes."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Max bytes"),
 *     required = FALSE
 *   ),
 *   consumes = {
 *     "constraints" = @ContextDefinition("any",
 *       label = @Translation("Resolved upload constraints"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class StagedUploadConstraintsMaxBytes extends DataProducerPluginBase {

  /**
   * Returns the maximum allowed file size in bytes.
   *
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   Resolved constraints (GraphQL parent value).
   *
   * @return int
   *   Maximum bytes allowed for the target.
   */
  public function resolve(ResolvedUploadConstraints $constraints): int {
    return $constraints->maxBytes;
  }

}
