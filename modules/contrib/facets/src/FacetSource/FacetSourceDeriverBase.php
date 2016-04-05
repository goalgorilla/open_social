<?php

namespace Drupal\facets\FacetSource;


use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for facet source derivers.
 */
abstract class FacetSourceDeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static();

    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    $deriver->setEntityTypeManager($entity_type_manager);

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $deriver->setStringTranslation($translation);

    return $deriver;
  }

  /**
   * Retrieves the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManager
   *   The entity manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * Sets the entity manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManager $entity_type_manager) {
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
