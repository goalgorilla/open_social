<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the platform branding preferred features information.
 *
 * @DataProducer(
 *   id = "platform_branding_preferred_features",
 *   name = @Translation("Platform Branding Preferred Features"),
 *   description = @Translation("The platform branding preferred features information."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Platform Branding Preferred Features")
 *   )
 * )
 */
class PlatformBrandingPreferredFeatures extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * PlatformBranding constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * Returns platform branding preferred features information.
   *
   * @return array
   *   An array with the preferred features.
   */
  public function resolve() : array {
    $preferred_features = $this->moduleHandler->invokeAll('social_branding_preferred_features');
    $this->moduleHandler->alter('social_branding_preferred_features', $preferred_features);

    // Order ascending by weight.
    usort($preferred_features, function ($item1, $item2) {
      return $item1->getWeight() <=> $item2->getWeight();
    });

    return $preferred_features;
  }

}
