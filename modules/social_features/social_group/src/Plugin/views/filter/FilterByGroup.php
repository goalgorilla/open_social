<?php

namespace Drupal\social_group\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide filtration by group name.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("filter_by_group")
 */
class FilterByGroup extends InOperator {

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The entity type bundle service.
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [];
    $groups = [];

    // Get all available groups.
    $query = $this->database->select('groups_field_data', 'gfd');
    $query->condition('default_langcode', '1');
    $query->fields('gfd', ['type', 'id', 'label']);
    $execution = $query->execute();
    if ($execution) {
      $groups = $execution->fetchAll();
    }

    // Create an options group for every group bundle.
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('group');
    foreach ($groups as $group) {
      $bundle_label = (string) $bundles[$group->type]['label'];
      $this->valueOptions[$bundle_label][$group->id] = $group->label;
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    parent::valueForm($form, $form_state);
    $form['value']['#type'] = 'select2';
    $form['value']['#select2'] = [
      'closeOnSelect' => FALSE,
      'placeholder' => $this->t('- Any -'),
    ];
  }

}
