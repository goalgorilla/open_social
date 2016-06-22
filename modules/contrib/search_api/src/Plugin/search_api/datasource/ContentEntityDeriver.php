<?php

namespace Drupal\search_api\Plugin\search_api\datasource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives a datasource plugin definition for every content entity type.
 *
 * @see \Drupal\search_api\Plugin\search_api\datasource\ContentEntityDatasource
 */
class ContentEntityDeriver implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static();

    /** @var $entity_type_manager \Drupal\Core\Entity\EntityTypeManagerInterface */
    $entity_type_manager = $container->get('entity_type.manager');
    $deriver->setEntityTypeManager($entity_type_manager);

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $deriver->setStringTranslation($translation);

    return $deriver;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
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
      $plugin_derivatives = array();
      foreach ($this->getEntityTypeManager()->getDefinitions() as $entity_type => $entity_type_definition) {
        // We only support content entity types at the moment, since config
        // entities don't implement \Drupal\Core\TypedData\ComplexDataInterface.
        if ($entity_type_definition instanceof ContentEntityType) {
          $plugin_derivatives[$entity_type] = array(
            'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $entity_type,
            'entity_type' => $entity_type,
            'label' => $entity_type_definition->getLabel(),
            'description' => $this->t('Provides %entity_type entities for indexing and searching.', array('%entity_type' => $entity_type_definition->getLabel())),
          ) + $base_plugin_definition;
        }
      }

      uasort($plugin_derivatives, array($this, 'compareDerivatives'));

      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];
  }

  /**
   * Compares two plugin definitions according to their labels.
   *
   * @param array $a
   *   A plugin definition, with at least a "label" key.
   * @param array $b
   *   Another plugin definition.
   *
   * @return int
   *   An integer less than, equal to, or greater than zero if the first
   *   argument is considered to be respectively less than, equal to, or greater
   *   than the second.
   */
  public function compareDerivatives(array $a, array $b) {
    return strnatcasecmp($a['label'], $b['label']);
  }

}
