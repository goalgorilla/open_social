<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\ImageDimension;

/**
 * Resolves image width from staged upload constraint dimensions.
 *
 * @DataProducer(
 *   id = "staged_upload_constraints_dimensions_width",
 *   name = @Translation("Staged upload constraints dimensions width"),
 *   description = @Translation("Returns the width from an ImageDimension value."),
 *   produces = @ContextDefinition("integer",
 *     label = @Translation("Width"),
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
class StagedUploadConstraintsDimensionsWidth extends DataProducerPluginBase {

  /**
   * Returns the media width in pixels.
   *
   * @param \Drupal\signed_file_upload\DataObject\ImageDimension $dimensions
   *   Dimension bounds (GraphQL parent value).
   *
   * @return int
   *   Width in pixels.
   */
  public function resolve(ImageDimension $dimensions): int {
    return $dimensions->width;
  }

}
