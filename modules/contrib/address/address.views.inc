<?php

/**
 * @file
 * Provide Views data for the Address module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * Views integration for address fields.
 */
function address_field_views_data(FieldStorageConfigInterface $field) {
  // Provide a field handler for each individual column.
  $columns = [
    'country_code' => 'country_code',
    'administrative_area' => 'subdivision',
    'locality' => 'subdivision',
    'dependent_locality' => 'subdivision',
    'postal_code' => 'standard',
    'sorting_code' => 'standard',
    'address_line1' => 'standard',
    'address_line2' => 'standard',
    'organization' => 'standard',
    'recipient' => 'standard',
  ];

  $data = views_field_default_views_data($field);
  $field_name = $field->getName();
  foreach ($data as $table_name => $table_data) {
    foreach ($columns as $column => $plugin_id) {
      $data[$table_name][$field_name . '_' . $column]['field'] = [
        'id' => $plugin_id,
        'field_name' => $field_name,
        'property' => $column,
      ];
    }
    // Add the custom country_code filter.
    $data[$table_name][$field_name . '_country_code']['filter']['id'] = 'country_code';
  }

  return $data;
}
