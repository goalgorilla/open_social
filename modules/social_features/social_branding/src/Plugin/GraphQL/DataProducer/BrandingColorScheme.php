<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The brand colors for this community.
 *
 * @DataProducer(
 *   id = "branding_color_scheme",
 *   name = @Translation("Community Branding Colors"),
 *   description = @Translation("The brand colors for this community."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Community Branding Colors")
 *   ),
 *   consumes = {
 *     "communityBranding" = @ContextDefinition("any",
 *       label = @Translation("Community Branding"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class BrandingColorScheme extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
   * BrandingColorScheme constructor.
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
   * Returns community branding colors.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $community_branding
   *   The community branding.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The community branding colors.
   */
  public function resolve(ImmutableConfig $community_branding) : ?ImmutableConfig {
    if ($community_branding->get('default') === 'socialblue') {
      if ($this->config->get('color.theme.socialblue')->get('palette.brand-primary')) {
        return $this->config->get('color.theme.socialblue');
      }

      return $this->config->get('socialblue.settings');
    }

    return NULL;
  }

}
