<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The brand colors for this platform.
 *
 * @DataProducer(
 *   id = "platform_branding_colors",
 *   name = @Translation("Platform Branding Colors"),
 *   description = @Translation("The brand colors for this platform."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Platform Branding Colors")
 *   ),
 *   consumes = {
 *     "platformBranding" = @ContextDefinition("any",
 *       label = @Translation("Platform Branding"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class PlatformBrandingColors extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
   * Returns platform branding colors.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $platform_branding
   *   The platform branding.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The platform branding colors.
   */
  public function resolve(ImmutableConfig $platform_branding) : ?ImmutableConfig {
    if ($platform_branding->get('default') === 'socialblue') {
      if ($this->config->get('color.theme.socialblue')->get('palette.brand-primary')) {
        return $this->config->get('color.theme.socialblue');
      }

      return $this->config->get('socialblue.settings');
    }

    return NULL;
  }

}
