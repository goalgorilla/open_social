<?php

/**
 * @file
 * Contains \Drupal\field_group_migrate\Plugin\migrate\source\d6\FieldGroup.
 */

namespace Drupal\field_group_migrate\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 field_group source.
 *
 * @MigrateSource(
 *   id = "d6_field_group"
 * )
 */
class FieldGroup extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('content_group', 'g')
    ->fields('g', [
      'group_type',
      'type_name',
      'group_name',
      'label',
      'settings',
      'weight',
    ]);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $query = $this->select('content_group_fields', 'f');
    $query->fields('f', ['field_name'])
      ->condition('type_name', $row->getSourceProperty('type_name'))
      ->condition('group_name', $row->getSourceProperty('group_name'));
    $fields = $query->execute()->fetchCol();
    $row->setSourceProperty('children', $fields);
    $row->setSourceProperty('settings', unserialize($row->getSourceProperty('settings')));

    switch ($row->getSourceProperty('constants/mode')) {
      case 'entity_form_display':
        $this->transformEntityFormDisplaySettings($row);
        break;

      case 'entity_view_display':
        $this->transformEntityViewDisplaySettings($row);
        break;
    }

    return parent::prepareRow($row);
  }

  protected function transformEntityFormDisplaySettings(Row $row) {
    $row->setSourceProperty('extracted_settings', $row->getSourceProperty('settings/form'));
    $source_settings = $row->getSourceProperty('extracted_settings');
    $settings = [
      'format_type' => 'details',
      'format_settings' => [],
    ];

    switch ($source_settings['style']) {
      case 'no_style':
        $settings['format_type'] = 'no_style';
        break;

      case 'simple':
        $settings['format_type'] = 'html_element';
        $settings['format_settings']['element'] = 'div';
        $settings['format_settings']['label_element'] = 'h2';
        break;

      case 'fieldset':
        $settings['format_type'] = 'fieldset';
        break;

      case 'fieldset_collapsible':
        $settings['format_type'] = 'details';
        $settings['format_settings']['open'] = TRUE;
        break;

      case 'fieldset_collapsed':
        $settings['format_type'] = 'details';
        $settings['format_settings']['open'] = FALSE;
        break;

      case 'hidden':
        $settings['format_type'] = 'hidden';
        break;
    }

    $row->setSourceProperty('converted_settings', $settings);
  }

  protected function transformEntityViewDisplaySettings(Row $row) {
    $row->setSourceProperty('extracted_settings', $row->getSourceProperty('settings/display'));
    $view_modes = array_diff(array_keys($row->getSourceProperty('extracted_settings')), ['label', 'description', 'weight']);
    $view_modes = array_filter($view_modes, function ($value) {
      return !is_numeric($value);
    });
    $row->setSourceProperty('view_mode_keys', $view_modes);
    $view_modes = [];

    foreach ($row->getSourceProperty('view_mode_keys') as $view_mode) {
      $source_settings = $row->getSourceProperty('extracted_settings/' . $view_mode);
      $row->setSourceProperty('view_modes', []);
      $settings = [
        'format_type' => 'details',
        'format_settings' => [],
      ];

      switch ($source_settings['format']) {
        case 'no_style':
          $settings['format_type'] = 'no_style';
          break;

        case 'simple':
          $settings['format_type'] = 'html_element';
          $settings['format_settings']['element'] = 'div';
          $settings['format_settings']['label_element'] = 'h2';
          break;

        case 'fieldset':
          $settings['format_type'] = 'fieldset';
          break;

        case 'fieldset_collapsible':
          $settings['format_type'] = 'details';
          $settings['format_settings']['open'] = TRUE;
          break;

        case 'fieldset_collapsed':
          $settings['format_type'] = 'details';
          $settings['format_settings']['open'] = FALSE;
          break;

        case 'hidden':
          $settings['format_type'] = 'hidden';
          break;
      }

      /**
       * @todo: ?
       */
      if ($view_mode == 'full') {
        $view_mode = 'default';
      }

      // $row->setSourceProperty('view_modes/' . $view_mode, $settings);
      $view_modes[$view_mode] = $settings;
    }

    $row->setSourceProperty('view_modes', $view_modes);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type_name']['type'] = 'string';
    $ids['type_name']['alias'] = 'g';

    $ids['group_name']['type'] = 'string';
    $ids['group_name']['alias'] = 'g';

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'group_type',
      'type_name',
      'group_name',
      'label',
      'settings',
      'weight'
    ];
    return array_combine($fields, $fields);
  }

}
