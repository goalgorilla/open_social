<?php

namespace Drupal\secret_file_system;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Site\Settings;
use Drupal\secret_file_system\StreamWrapper\SecretStream;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register the secret file system if a secret file path is configured.
 */
class SecretFileSystemServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) : void {
    // Only register the secret file stream wrapper if a private file path has
    // been set.
    if (Settings::get('file_private_path')) {
      $container->register('secret_file_system.secret_response_cache_subscriber', SecretResponseCacheSubscriber::class)
        ->addArgument(new Reference('datetime.time'))
        ->addTag('event_subscriber');

      $container->register('stream_wrapper.secret', SecretStream::class)
        ->addTag('stream_wrapper', ['scheme' => 'secret']);
    }
  }

}
