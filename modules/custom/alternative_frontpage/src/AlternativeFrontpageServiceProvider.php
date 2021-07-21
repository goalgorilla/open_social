<?php

namespace Drupal\alternative_frontpage;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the PathMatcher service.
 */
class AlternativeFrontpageServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    // Adds entity_type.manager service as an additional argument.
    $definition = $container->getDefinition('path.matcher');
    $definition->setClass('Drupal\alternative_frontpage\AlternativeFrontpagePathMatcher');
  }

}
