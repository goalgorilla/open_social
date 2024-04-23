<?php

namespace Drupal\social_core;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Site\Settings;
use Drupal\social_core\StreamWrapper\SecretStream;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SocialCoreServiceProvider.
 *
 * @package Drupal\social_core
 */
class SocialCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('entity.autocomplete_matcher');
    $definition->setClass('Drupal\social_core\Entity\EntityAutocompleteMatcher');

    if (is_array($modules = $container->getParameter('container.modules'))) {
      // Check if select 2 is installed before we get the definition, otherwise
      // you get a requested a non-existent service
      // "select2.autocomplete_matcher" on update hooks.
      if (isset($modules['select2'])) {
        $definition = $container->getDefinition('select2.autocomplete_matcher');
        $definition->setClass('Drupal\social_core\Entity\Select2EntityAutocompleteMatcher');
      }
    }

    // Only register the secret file stream wrapper if a private file path has
    // been set.
    if (Settings::get('file_private_path')) {
      $container->register('social_core.secret_response_cache_subscriber', SecretResponseCacheSubscriber::class)
        ->addArgument(new Reference('datetime.time'))
        ->addTag('event_subscriber');

      $container->register('stream_wrapper.secret', SecretStream::class)
        ->addTag('stream_wrapper', ['scheme' => 'secret']);
    }
  }

}
