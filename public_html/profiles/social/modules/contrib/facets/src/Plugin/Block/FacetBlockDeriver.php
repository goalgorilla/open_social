<?php

namespace Drupal\facets\Plugin\Block;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This deriver creates a block for every facet that has been created.
 */
class FacetBlockDeriver implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The entity storage used for facets.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface $facetStorage
   */
  protected $facetStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static($container, $base_plugin_id);
    $deriver->facetStorage = $container->get('entity_type.manager')->getStorage('facets_facet');

    return $deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    return isset($derivatives[$derivative_id]) ? $derivatives[$derivative_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $base_plugin_id = $base_plugin_definition['id'];

    if (!isset($this->derivatives[$base_plugin_id])) {
      $plugin_derivatives = [];

      /** @var \Drupal\facets\FacetInterface[] $all_facets */
      $all_facets = $this->facetStorage->loadMultiple();

      foreach ($all_facets as $facet) {
        $machine_name = $facet->id();

        $plugin_derivatives[$machine_name] = [
          'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $machine_name,
          'label' => $this->t('Facet: :facet', [':facet' => $facet->getName()]),
          'admin_label' => $facet->getName(),
          'description' => $this->t('Facet'),
        ] + $base_plugin_definition;

        $sources[] = $this->t('Facet: :facet', [':facet' => $facet->getName()]);
      }

      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];
  }

}
