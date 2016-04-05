<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\user\Entity\User;

/**
 * Provides a processor that transforms the results to show the user's name.
 *
 * @FacetsProcessor(
 *   id = "uid_to_username_callback",
 *   label = @Translation("Transform uid to username"),
 *   description = @Translation("Show the username instead, when the source field is a user id."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class UidToUserNameCallbackProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as &$result) {
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load($result->getRawValue());

      $result->setDisplayValue($user->getDisplayName());
    }

    return $results;
  }

}
