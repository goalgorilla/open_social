<?php

/**
 * @file
 * Contains Drupal\template_mapper\TemplateMapper.
 */

namespace Drupal\template_mapper;


use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Defines the Template mapper service.
 *
 * @todo, fill out interface.
 */
class TemplateMapper {
  /*
   * @var EntityManagerInterface
   */
  private $entityManager;


  /*
   * @var array of all mappings
   */
  private $allMappings;

  /**
   * Constructor.
   *
   * @param EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  public function setAllMappings($mappings) {
    $this->allMappings = $mappings;
  }

  /**
   * @todo, this function should not be necessary. Make a way to ask the entity
   * manager just for mappings appropriate to the given hook.
   */
  private function getAllMappings() {
    if (empty($this->allMappings)) {
      // @todo, use caching.
      $template_mappings = $this->entityManager->getStorage('template_mapping')->loadMultiple(NULL);
      $all_mappings = array();
      foreach ($template_mappings as $template_mapping) {
        $all_mappings[$template_mapping->id()] = $template_mapping->getMapping();
      }
      $this->setAllMappings($all_mappings);
    }
    return $this->allMappings;
  }

  public function performMapping($existing_suggestions, $hook) {

    // @todo, getAllMappings is not filtering down by hook at all anymore.
    // Restore that.
    $replacements = $this->getAllMappings($hook);

    $new_suggestions = array();
    foreach ($existing_suggestions as $suggestion) {

      if (array_key_exists($suggestion, $replacements)) {
        $new_suggestions[] = $replacements[$suggestion];
      }
      else {
        $new_suggestions[] = $suggestion;
      }

    }
    return $new_suggestions;
  }
}
