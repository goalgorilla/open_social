<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\PreferredPlatformFeature;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The machine name of the preferred feature.
 *
 * @DataProducer(
 *   id = "platform_feature_machine_name",
 *   name = @Translation("Platform Feature Machine Name"),
 *   description = @Translation("The machine name of the preferred feature."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Preferred Feature Machine Name")
 *   ),
 *   consumes = {
 *     "preferredFeature" = @ContextDefinition("any",
 *       label = @Translation("Preferred Feature"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class PlatformFeatureMachineName extends EntityDataProducerPluginBase {

  /**
   * Returns the machine name of the preferred feature.
   *
   * @param \Drupal\social_branding\PreferredPlatformFeature $preferred_feature
   *   The preferred feature.
   *
   * @return string
   *   The machine name.
   */
  public function resolve(PreferredPlatformFeature $preferred_feature) : string {
    return $preferred_feature->getName();
  }

}
