<?php

namespace Drupal\social_language;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class SocialLanguageServiceProvider.
 *
 * @package Drupal\social_language
 */
class SocialLanguageServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('main_content_renderer.modal');
    $definition->setClass('Drupal\social_language\SocialLanguageModalRenderer');
  }

}
