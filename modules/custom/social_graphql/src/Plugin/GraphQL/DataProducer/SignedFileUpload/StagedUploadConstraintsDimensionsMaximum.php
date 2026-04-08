<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ImageDimension;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;

/**
 * Resolves maximum image dimensions from resolved upload constraints.
 *
 * @DataProducer(
 *   id = "staged_upload_constraints_dimensions_maximum",
 *   name = @Translation("Staged upload constraints dimensions maximum"),
 *   description = @Translation("Returns the maximum width/height when image bounds are defined."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Maximum dimensions"),
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
class StagedUploadConstraintsDimensionsMaximum extends DataProducerPluginBase {

  /**
   * Returns maximum image dimensions when the target supports image bounds.
   *
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   Resolved constraints (GraphQL parent value).
   *
   * @return \Drupal\signed_file_upload\DataObject\ImageDimension|null
   *   Maximum width and height, or NULL when not applicable.
   */
  public function resolve(ResolvedUploadConstraints $constraints): ?ImageDimension {
    return $constraints->imageDimensionBounds?->max;
  }

}
