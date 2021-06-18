<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The logo url.
 *
 * @DataProducer(
 *   id = "logo_url",
 *   name = @Translation("Logo url"),
 *   description = @Translation("The logo url."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Logo url.")
 *   ),
 *   consumes = {
 *     "logo" = @ContextDefinition("string",
 *       label = @Translation("Logo uri"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class LogoUrl extends EntityDataProducerPluginBase {

  /**
   * Returns the logo external url.
   *
   * @param string $logo_uri
   *   The logo uri.
   *
   * @return string|null
   *   The logo external url.
   */
  public function resolve(string $logo_uri) : ?string {
    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($logo_uri)) {
      return $wrapper->getExternalUrl();
    }
    return NULL;
  }

}
