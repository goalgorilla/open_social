<?php

namespace Drupal\address\Plugin\views\field;

use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the subdivision name instead of the id.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("subdivision")
 */
class Subdivision extends FieldPluginBase {

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs a Subdivision object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The id of the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (empty($value)) {
      return '';
    }

    $entity = $this->getEntity($values);
    $address = $entity->{$this->definition['field_name']}->first();
    switch ($this->definition['property']) {
      case 'administrative_area':
        $parent_id = NULL;
        $needs_parent = FALSE;
        break;
      case 'locality':
        $parent_id = $address->administrative_area;
        $needs_parent = TRUE;
        break;
      case 'dependent_locality':
        $parent_id = $address->locality;
        $needs_parent = TRUE;
        break;
    }

    if (!$needs_parent || !empty($parent_id)) {
      $subdivisions = $this->subdivisionRepository->getList($address->country_code, $parent_id);
      if (isset($subdivisions[$value])) {
        $value = $subdivisions[$value];
      }
    }

    return $this->sanitizeValue($value);
  }
}
