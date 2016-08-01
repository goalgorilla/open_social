<?php

namespace Drupal\address\Plugin\views\filter;

use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by country.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("country_code")
 */
class CountryCode extends InOperator {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Repository\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new CountryCode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CountryRepositoryInterface $country_repository, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->countryRepository = $country_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.country_repository'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $entity_type_id = $this->getEntityType();
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $field_name = $this->getFieldName();
      $available_countries = $this->getAvailableCountries($entity_type, $field_name);
      $countries = $this->countryRepository->getList();
      if (!empty($available_countries)) {
        $countries = array_intersect_key($countries, $available_countries);
      }

      $this->valueOptions = $countries;
    }

    return $this->valueOptions;
  }

  /**
   * Gets the name of the entity field on which this filter operates.
   *
   * @return string
   *   The field name.
   */
  protected function getFieldName() {
    if (isset($this->configuration['field_name'])) {
      // Configurable field.
      $field_name = $this->configuration['field_name'];
    }
    else {
      // Base field.
      $field_name = $this->configuration['entity field'];
    }

    return $field_name;
  }

  /**
   * Gets the list of available countries for the current entity field.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The current entity type.
   * @param string $field_name
   *   The current field name.
   *
   * @return array
   *   An array of country codes. Empty if the country list isn't restricted.
   */
  protected function getAvailableCountries(EntityTypeInterface $entity_type, $field_name) {
    $bundles = $this->getBundles($entity_type, $field_name);
    $storage = $this->entityTypeManager->getStorage($entity_type->id());
    $countries_by_bundle = [];
    foreach ($bundles as $bundle) {
      $values = [];
      if ($bundle_key = $entity_type->getKey('bundle')) {
        $values[$bundle_key] = $bundle;
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $storage->create($values);
      if ($entity->hasField($field_name)) {
        $countries_by_bundle[$bundle] = $entity->get($field_name)->appendItem()->getAvailableCountries();
      }
    }
    // Create the unified list, valid across bundles.
    // Start by filtering out lists that are empty cause no restrictions apply.
    $countries = [];
    $countries_by_bundle = array_filter($countries_by_bundle);
    if (count($countries_by_bundle) === 1) {
      $countries = reset($countries_by_bundle);
    }
    elseif (count($countries_by_bundle) > 1) {
      // Leave only the country codes that are common to all lists.
      $countries = array_pop($countries_by_bundle);
      foreach ($countries_by_bundle as $list) {
        $countries = array_intersect_key($countries, $list);
      }
    }

    return $countries;
  }

  /**
   * Gets the bundles for the current entity field.
   *
   * If the view has a non-exposed bundle filter, the bundles are taken from
   * there. Otherwise, the field's bundles are used.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The current entity type.
   * @param string $field_name
   *   The current field name.
   *
   * @return string[]
   *   The bundles.
   */
  protected function getBundles(EntityTypeInterface $entity_type, $field_name) {
    $bundles = [];
    $bundle_key = $entity_type->getKey('bundle');
    if ($bundle_key && isset($this->view->filter[$bundle_key])) {
      $filter = $this->view->filter[$bundle_key];
      if (!$filter->isExposed() && !empty($filter->value)) {
        // 'all' is added by Views and isn't a bundle.
        $bundles = array_diff($filter->value, ['all']);
      }
    }
    // Fallback to the list of bundles the field is attached to.
    if (empty($bundles)) {
      $map = $this->entityFieldManager->getFieldMap();
      $bundles = $map[$entity_type->id()][$field_name]['bundles'];
    }

    return $bundles;
  }

}
