<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the platform branding logo url.
 *
 * @DataProducer(
 *   id = "platform_branding_logo_url",
 *   name = @Translation("Platform Branding Logo Url"),
 *   description = @Translation("The Platform Branding Logo Url."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Platform Branding Logo Url")
 *   ),
 *   consumes = {
 *     "platformBranding" = @ContextDefinition("any",
 *       label = @Translation("Platform Branding"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class PlatformBrandingLogoUrl extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * PlatformBrandingLogoUrl constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory;
  }

  /**
   * Returns platform branding logo url.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $platform_branding
   *   The platform branding configuration.
   *
   * @return string|null
   *   The string with platform branding logo url.
   */
  public function resolve(ImmutableConfig $platform_branding) : ?string {
    if ($platform_branding->get('default') === 'socialblue') {
      if ($this->config->get('socialblue.settings')->get('logo.path')) {
        $wrapper = \Drupal::service('stream_wrapper_manager')
          ->getViaUri($this->config->get('socialblue.settings')->get('logo.path'));
        return $wrapper->getExternalUrl();
      }
    }

    return NULL;
  }

}
