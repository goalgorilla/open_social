<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\PreferredFeature;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The machine name of the feature.
 *
 * @DataProducer(
 *   id = "feature_machine_name",
 *   name = @Translation("Feature Machine Name"),
 *   description = @Translation("The machine name of the feature."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Feature Machine Name")
 *   ),
 *   consumes = {
 *     "preferredFeature" = @ContextDefinition("any",
 *       label = @Translation("Preferred Feature"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class FeatureMachineName extends EntityDataProducerPluginBase {

  /**
   * Returns the machine name of the preferred feature.
   *
   * @param \Drupal\social_branding\PreferredFeature $preferred_feature
   *   The preferred feature.
   *
   * @return string
   *   The machine name.
   */
  public function resolve(PreferredFeature $preferred_feature) : string {
    return $preferred_feature->getName();
  }

}
