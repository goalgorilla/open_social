<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ImageDimension;
use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;

/**
 * Resolves minimum image dimensions from resolved upload constraints.
 *
 * @DataProducer(
 *   id = "staged_upload_constraints_dimensions_minimum",
 *   name = @Translation("Staged upload constraints dimensions minimum"),
 *   description = @Translation("Returns the minimum width/height when image bounds are defined."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Minimum dimensions"),
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
class StagedUploadConstraintsDimensionsMinimum extends DataProducerPluginBase {

  /**
   * Returns minimum image dimensions when the target supports image bounds.
   *
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   Resolved constraints (GraphQL parent value).
   *
   * @return \Drupal\signed_file_upload\DataObject\ImageDimension|null
   *   Minimum width and height, or NULL when not applicable.
   */
  public function resolve(ResolvedUploadConstraints $constraints): ?ImageDimension {
    return $constraints->imageDimensionBounds?->min;
  }

}
