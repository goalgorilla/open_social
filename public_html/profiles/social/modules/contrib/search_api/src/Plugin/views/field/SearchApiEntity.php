<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the display of entity reference fields in Search API Views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_entity")
 */
class SearchApiEntity extends SearchApiStandard {

  use FieldAPIHandlerTrait;

  /**
   * The entity display repository manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $field */
    $field = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $field->setEntityDisplayRepository($container->get('entity_display.repository'));

    return $field;
  }

  /**
   * Retrieves the entity display repository.
   *
   * @return \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   *   The entity entity display repository.
   */
  public function getEntityDisplayRepository() {
    return $this->entityDisplayRepository ?: \Drupal::service('entity_display.repository');
  }

  /**
   * Sets the entity display repository.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The new entity display repository.
   *
   * @return $this
   */
  public function setEntityDisplayRepository(EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityDisplayRepository = $entity_display_repository;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['display_methods'] = array('default' => array());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type_id = $this->getTargetEntityTypeId();
    $view_modes = array();
    $bundles = array();
    if ($entity_type_id) {
      $bundles = $this->getEntityManager()->getBundleInfo($entity_type_id);
      // In case the field definition specifies the bundles to expect, restrict
      // the displayed bundles to those.
      $settings = $this->getFieldDefinition()->getSettings();
      if (!empty($settings['handler_settings']['target_bundles'])) {
        $bundles = array_intersect_key($bundles, $settings['handler_settings']['target_bundles']);
      }
      foreach ($bundles as $bundle => $info) {
        $view_modes[$bundle] = $this->getEntityDisplayRepository()
          ->getViewModeOptionsByBundle($entity_type_id, $bundle);
      }
    }

    foreach ($bundles as $bundle => $info) {
      $args['@bundle'] = $info['label'];
      $form['display_methods'][$bundle]['display_method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Display for "@bundle" bundle', $args),
        '#options' => array(
          '' => $this->t('Hide'),
          'id' => $this->t('Raw ID'),
          'label' => $this->t('Only label'),
        ),
        '#default_value' => 'label',
      );
      if (isset($this->options['display_methods'][$bundle]['display_method'])) {
        $form['display_methods'][$bundle]['display_method']['#default_value'] = $this->options['display_methods'][$bundle]['display_method'];
      }
      if (!empty($view_modes[$bundle])) {
        $form['display_methods'][$bundle]['display_method']['#options']['view_mode'] = $this->t('Entity view');
        if (count($view_modes[$bundle]) > 1) {
          $form['display_methods'][$bundle]['view_mode'] = array(
            '#type' => 'select',
            '#title' => $this->t('View mode for "@bundle" bundle', $args),
            '#options' => $view_modes[$bundle],
            '#states' => array(
              'visible' => array(
                ':input[name="options[display_methods][' . $bundle . '][display_method]"]' => array(
                  'value' => 'view_mode',
                ),
              ),
            ),
          );
          if (isset($this->options['display_methods'][$bundle]['view_mode'])) {
            $form['display_methods'][$bundle]['view_mode']['#default_value'] = $this->options['display_methods'][$bundle]['view_mode'];
          }
        }
        else {
          reset($view_modes[$bundle]);
          $form['display_methods'][$bundle]['view_mode'] = array(
            '#type' => 'value',
            '#value' => key($view_modes[$bundle]),
          );
        }
      }
      if (count($bundles) == 1) {
        $form['display_methods'][$bundle]['display_method']['#title'] = $this->t('Display method');
        if (!empty($form['display_methods'][$bundle]['view_mode'])) {
          $form['display_methods'][$bundle]['view_mode']['#title'] = $this->t('View mode');
        }
      }
    }

    $form['link_to_item']['#description'] .= ' ' . $this->t('This will only take effect for entities for which only the entity label is displayed.');
    $form['link_to_item']['#weight'] = 5;
  }

  /**
   * Return the entity type ID of the entity this field handler should display.
   *
   * @return string|null
   *   The entity type ID, or NULL if it couldn't be found.
   */
  public function getTargetEntityTypeId() {
    $field_definition = $this->getFieldDefinition();
    if ($field_definition->getType() === 'field_item:comment') {
      return 'comment';
    }
    return $field_definition->getSetting('target_type');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addRetrievedProperty($this->getCombinedPropertyPath());
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // The parent method will just have loaded the entity IDs. We now multi-load
    // the actual objects.
    $property_path = $this->getCombinedPropertyPath();
    foreach ($values as $i => $row) {
      if (!empty($row->{$property_path})) {
        foreach ((array) $row->{$property_path} as $j => $value) {
          if (is_scalar($value)) {
            $to_load[$value][] = array($i, $j);
          }
        }
      }
    }

    if (empty($to_load)) {
      return;
    }

    $entities = $this->getEntityManager()
      ->getStorage($this->getTargetEntityTypeId())
      ->loadMultiple(array_keys($to_load));
    $account = $this->getQuery()->getAccessAccount();
    foreach ($entities as $id => $entity) {
      foreach ($to_load[$id] as list($i, $j)) {
        if ($entity->access('view', $account)) {
          $values[$i]->{$property_path}[$j] = $entity;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    if (is_array($item['value'])) {
      return $this->getRenderer()->render($item['value']);
    }
    return parent::render_item($count, $item);
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $property_path = $this->getCombinedPropertyPath();
    if (!empty($values->{$property_path})) {
      $items = array();
      foreach ((array) $values->{$property_path} as $value) {
        if ($value instanceof EntityInterface) {
          $item = $this->getItem($value);
          if ($item) {
            $items[] = $item;
          }
        }
      }
      return $items;
    }
    return array();
  }

  /**
   * Creates an item for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array|null
   *   NULL if the entity should not be displayed. Otherwise, an associative
   *   array with at least "value" set, to either a string or a render array,
   *   and possibly also additional alter options.
   */
  protected function getItem(EntityInterface $entity) {
    $bundle = $entity->bundle();
    if (empty($this->options['display_methods'][$bundle]['display_method'])) {
      return NULL;
    }

    $display_method = $this->options['display_methods'][$bundle]['display_method'];
    if (in_array($display_method, array('id', 'label'))) {
      if ($display_method == 'label') {
        $item['value'] = $entity->label();
      }
      else {
        $item['value'] = $entity->id();
      }

      if ($this->options['link_to_item']) {
        $item['make_link'] = TRUE;
        $item['url'] = $entity->toUrl('canonical');
      }

      return $item;
    }

    $view_mode = $this->options['display_methods'][$bundle]['view_mode'];
    $build = $this->getEntityManager()
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, $view_mode);
    return array(
      'value' => $build,
    );
  }

}
