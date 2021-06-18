<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the platform theme information.
 *
 * @DataProducer(
 *   id = "platform_theme",
 *   name = @Translation("Platform Theme"),
 *   description = @Translation("The appearance information."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Platform Theme")
 *   )
 * )
 */
class PlatformTheme extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
   * PlatformTheme constructor.
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
   * Returns platform theme information.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The platform theme configuration.
   */
  public function resolve() : ?ImmutableConfig {
    if ($this->config->get('color.theme.socialblue')->get('palette.brand-primary')) {
      return $this->config->get('color.theme.socialblue');
    }

    return $this->config->get('socialblue.settings');
  }

}
