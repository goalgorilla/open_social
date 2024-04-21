<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_branding\PreferredFeature;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the preferred features information.
 *
 * @DataProducer(
 *   id = "preferred_features",
 *   name = @Translation("Preferred Features"),
 *   description = @Translation("The preferred features information."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Preferred Features")
 *   )
 * )
 */
class PreferredFeatures extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PreferredFeatures|ContainerFactoryPluginInterface|static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * PreferredFeatures constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config,) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->config = $config;
  }

  /**
   * Returns preferred features information.
   *
   * @return array
   *   An array with the preferred features.
   */
  public function resolve() : array {
    $preferred_features = $this->moduleHandler->invokeAll('social_branding_preferred_features');
    // Grab the saved config, if there is config already saved.
    $config = $this->config->getEditable('social_branding.settings');
    $features = $config->get('features');
    if (!empty($features)) {
      // If we have saved the config, this means at this moment in time,
      // even if there are things provided in the hook, we expect the
      // form to have been submitted so that will take priority.
      $preferred_features = [];
      foreach ($features as $name => $weight) {
        $weight = $weight['weight'];
        $preferred_features[] = new PreferredFeature($name, $weight);
      }
    }

    // Make sure the alter hook still works.
    $this->moduleHandler->alter('social_branding_preferred_features', $preferred_features);

    // Order ascending by weight.
    usort($preferred_features, function ($item1, $item2) {
      return $item1->getWeight() <=> $item2->getWeight();
    });

    return $preferred_features;
  }

}
