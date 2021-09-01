<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * Get the community about name.
 *
 * @DataProducer(
 *   id = "about_name",
 *   name = @Translation("Community About Name"),
 *   description = @Translation("The the community about name."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Community about name")
 *   ),
 *   consumes = {
 *     "communityAbout" = @ContextDefinition("any",
 *       label = @Translation("Community about"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class AboutName extends EntityDataProducerPluginBase {

  /**
   * Returns the community about name.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $community_about
   *   The community about.
   *
   * @return string
   *   The community name.
   */
  public function resolve(ImmutableConfig $community_about) : string {
    return $community_about->get('name');
  }

}
