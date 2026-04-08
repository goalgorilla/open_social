<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ImageDimension;

/**
 * Resolves image height from staged upload constraint dimensions.
 *
 * @DataProducer(
 *   id = "staged_upload_constraints_dimensions_height",
 *   name = @Translation("Staged upload constraints dimensions height"),
 *   description = @Translation("Returns the height from an ImageDimension value."),
 *   produces = @ContextDefinition("integer",
 *     label = @Translation("Height"),
 *     required = TRUE
 *   ),
 *   consumes = {
 *     "dimensions" = @ContextDefinition("any",
 *       label = @Translation("Image dimensions"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class StagedUploadConstraintsDimensionsHeight extends DataProducerPluginBase {

  /**
   * Returns the media height in pixels.
   *
   * @param \Drupal\signed_file_upload\DataObject\ImageDimension $dimensions
   *   Dimension bounds (GraphQL parent value).
   *
   * @return int
   *   Height in pixels.
   */
  public function resolve(ImageDimension $dimensions): int {
    return $dimensions->height;
  }

}
