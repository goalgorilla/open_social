<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;

/**
 * Resolves allowed file extensions from resolved upload constraints.
 *
 * @DataProducer(
 *   id = "staged_upload_constraints_allowed_extensions",
 *   name = @Translation("Staged upload constraints allowed extensions"),
 *   description = @Translation("Returns the list of allowed extensions."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Allowed extensions"),
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
class StagedUploadConstraintsAllowedExtensions extends DataProducerPluginBase {

  /**
   * Returns allowed file extensions for the upload target.
   *
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   Resolved constraints (GraphQL parent value).
   *
   * @return string[]
   *   Extensions without a leading dot.
   */
  public function resolve(ResolvedUploadConstraints $constraints): array {
    return $constraints->allowedExtensions;
  }

}
