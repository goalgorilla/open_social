<?php

namespace Drupal\social_language;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

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
    $definition = $container->getDefinition('url_generator');
    $definition->setClass('Drupal\social_language\SocialLanguageMetadataBubblingUrlGenerator')
      ->addArgument(new Reference('language_manager'));
  }

}
