<?php

namespace Drupal\social_content_block;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters service definitions.
 *
 * @package Drupal\social_content_block
 */
class ContentBlockServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // If the container has data policy manager services.
    if ($container->hasDefinition('social_content_block.override')) {
      $definition = $container->getDefinition('social_content_block.override');
      $definition->addArgument(new Reference('plugin.manager.content_block'));
    }
  }

}
