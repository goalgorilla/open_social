<?php

/**
 * @file
 * Field_group.field_ui.inc is a file that contains most functions
 * needed on the Fields UI Manage forms (display and fields).
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
Use Drupal\Core\Link;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\field_ui\Form\EntityDisplayFormBase;

/**
 * Helper function to get the form parameters to use while
 * building the fields and display overview form.
 *
 * @param $form
 *
 * @param \Drupal\Core\Entity\EntityDisplayBase $display
 *
 * @return \stdClass
 */
function field_group_field_ui_form_params($form, \Drupal\Core\Entity\EntityDisplayBase $display) {
  $entity_type_definition = \Drupal::entityManager()->getDefinition($form['#entity_type']);

  $params = new stdClass();
  $params->entity_type = $display->getTargetEntityTypeId();
  $params->bundle = $display->getTargetBundle();
  $params->route_parameters = Drupal\field_ui\FieldUI::getRouteBundleParameter($entity_type_definition, $form['#bundle']);
  $params->region_callback = 'field_group_display_overview_row_region';
  $params->mode = $display->getMode();
  $params->context = field_group_get_context_from_display($display);

  $params->groups = array();
  $params->groups = field_group_info_groups($params->entity_type, $params->bundle, $params->context, $params->mode);

  // Gather parenting data.
  $params->parents = array();
  foreach ($params->groups as $name => $group) {
    foreach ($group->children as $child) {
      $params->parents[$child] = $name;
    }
  }

  return $params;
}

/**
 * Helper function to get context from entity display.
 *
 * @param \Drupal\Core\Entity\EntityDisplayBase $display
 *
 * @return string
 */
function field_group_get_context_from_display(\Drupal\Core\Entity\EntityDisplayBase $display) {
  if ($display instanceof \Drupal\Core\Entity\Display\EntityFormDisplayInterface) {
    return 'form';
  }
  elseif ($display instanceof \Drupal\Core\Entity\Display\EntityViewDisplayInterface) {
    return 'view';
  }

  throw new LogicException('Unknown display object.');
}

/**
 * Function to alter the display overview screens.
 */
function field_group_field_ui_display_form_alter(&$form, FormStateInterface $form_state) {
  // Only start altering the form if we need to.
  if (empty($form['#fields']) && empty($form['#extra'])) {
    return;
  }

  $callback_object = $form_state->getBuildInfo()['callback_object'];
  if (!$callback_object instanceof EntityDisplayFormBase) {
    throw new InvalidArgumentException('Unkown callback object.');
  }

  $display = $callback_object->getEntity();

  $params = field_group_field_ui_form_params($form, $display);
  $form['#fieldgroups'] = array_keys($params->groups);
  $form['#context'] = $display;

  $table = &$form['fields'];
  $form_state_values = $form_state->getValues();
  $field_group_form_state = $form_state->get('field_group');

  // Add a region for 'add_new' rows, but only when fields are
  // available and thus regions.
  if (isset($table['#regions'])) {
    $table['#regions'] += array(
      'add_new' => array(
        'rows_order' => array('_add_new_group'),
      ),
    );
  }

  // Extend available parenting options.
  foreach ($params->groups as $name => $group) {
    $table['#parent_options'][$name] = $group->label;
  }
  $table['#parent_options']['_add_new_group'] = t('Add new group');

  // Update existing rows accordingly to the parents.
  foreach (Element::children($table) as $name) {
    $table[$name]['parent_wrapper']['parent']['#options'] = $table['#parent_options'];
    // Inherit the value of the parent when default value is empty.
    if (empty($table[$name]['parent_wrapper']['parent']['#default_value'])) {
      $table[$name]['parent_wrapper']['parent']['#default_value'] = isset($params->parents[$name]) ? $params->parents[$name] : '';
    }
  }

  $formatter_options = field_group_field_formatter_options($params->context);

  $refresh_rows = isset($form_state_values['refresh_rows']) ? $form_state_values['refresh_rows'] : (isset($form_state->getUserInput()['refresh_rows']) ? $form_state->getUserInput()['refresh_rows'] : NULL);
  // Create the group rows and check actions.
  foreach ($form['#fieldgroups'] as $name) {

    // Play around with form_sxtate so we only need to hold things
    // between requests, until the save button was hit.
    if (isset($form_state_values['refresh_rows'][$name])) {
      $group = & $field_group_form_state[$name];
    }
    else {
      $group = & $params->groups[$name];
    }

    $config_name_suffix = $group->entity_type . '.' . $group->bundle . '.' . $group->context . '.' . $group->mode . '.' . $group->group_name;

    // Check the currently selected formatter, and merge persisted values for
    // formatter settings for the group.
    // This needs to be done first, so all fields are updated before creating form elements.
    if (isset($refresh_rows) && $refresh_rows == $name) {
      $settings = isset($form_state_values['fields'][$name]) ? $form_state_values['fields'][$name] : (isset($form_state->getUserInput()['fields'][$name]) ? $form_state->getUserInput()['fields'][$name] : NULL);
      if (array_key_exists('settings_edit', $settings)) {
        $group = $field_group_form_state[$name];
      }
      field_group_formatter_row_update($group, $settings);
    }

    // Save the group when the configuration is submitted.
    if (!empty($form_state_values[$name . '_plugin_settings_update'])) {
      field_group_formatter_settings_update($group, $form_state_values['fields'][$name]);
    }
    // After all updates are finished, let the form_state know.
    $field_group_form_state[$name] = $group;

    $settings = field_group_format_settings_form($group, $form, $form_state);

    $id = strtr($name, '_', '-');
    $js_rows_data[$id] = array('type' => 'group', 'name' => $name);
    // A group cannot be selected as its own parent.
    $parent_options = $table['#parent_options'];
    unset($parent_options[$name]);
    $table[$name] = array(
      '#attributes' => array('class' => array('draggable', 'field-group'), 'id' => $id),
      '#row_type' => 'group',
      '#region_callback' => $params->region_callback,
      '#js_settings' => array('rowHandler' => 'group'),
      'human_name' => array(
        '#markup' => SafeMarkup::checkPlain(t($group->label)),
        '#prefix' => '<span class="group-label">',
        '#suffix' => '</span>',
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#default_value' => $group->weight,
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#options' =>  $parent_options,
          '#empty_value' => '',
          '#default_value' => isset($params->parents[$name]) ? $params->parents[$name] : '',
          '#attributes' => array('class' => array('field-parent')),
          '#parents' => array('fields', $name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
    );

    // For view settings. Add a spacer cell. We can't use colspan because of the javascript .
    if ($params->context == 'view') {
      $table[$name] += array(
        'spacer' => array(
          '#markup' => '&nbsp;'
        )
      );
    }

    $table[$name] += array(
      'format' => array(
        'type' => array(
          '#type' => 'select',
          '#options' => $formatter_options,
          '#default_value' => $group->format_type,
          '#attributes' => array('class' => array('field-group-type')),
        ),
      ),
    );

    $base_button = array(
      '#submit' => array(
        array($form_state->getBuildInfo()['callback_object'], 'multistepSubmit')
      ),
      '#ajax' => array(
        'callback' => array($form_state->getBuildInfo()['callback_object'], 'multistepAjax'),
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ),
      '#field_name' => $name,
    );

    if ($form_state->get('plugin_settings_edit') == $name) {
      $table[$name]['format']['#cell_attributes'] = array('colspan' => 2);
      $table[$name]['format']['format_settings'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
        '#parents' => array('fields', $name, 'settings_edit_form'),
        '#weight' => -5,
        'label' => array(
          '#markup' => t('Field group format:') . ' <span class="formatter-name">' . $group->format_type . '</span>',
        ),
        // Create a settings form where hooks can pick in.
        'settings' => $settings,
        'actions' => array(
          '#type' => 'actions',
          'save_settings' => $base_button + array(
            '#type' => 'submit',
            '#name' => $name . '_plugin_settings_update',
            '#value' => t('Update'),
            '#op' => 'update',
          ),
          'cancel_settings' => $base_button + array(
            '#type' => 'submit',
            '#name' => $name . '_plugin_settings_cancel',
            '#value' => t('Cancel'),
            '#op' => 'cancel',
            // Do not check errors for the 'Cancel' button.
            '#limit_validation_errors' => array(),
          ),
        ),
      );
      $table[$name]['#attributes']['class'][] = 'field-formatter-settings-editing';
      $table[$name]['format']['type']['#attributes']['class'] = array('visually-hidden');
    }
    else {
      // After saving, the settings are updated here aswell. First we create
      // the element for the table cell.
      $table[$name]['settings_summary'] = array('#markup' => '');
      if (!empty($group->format_settings)) {
        $table[$name]['settings_summary'] = field_group_format_settings_summary($name, $group);
      }
      // Add the configure button.
      $table[$name]['settings_edit'] = $base_button + array(
        '#type' => 'image_button',
        '#name' => $name . '_group_settings_edit',
        '#src' => 'core/misc/icons/787878/cog.svg',
        '#attributes' => array('class' => array('field-plugin-settings-edit'), 'alt' => t('Edit')),
        '#op' => 'edit',
            // Do not check errors for the 'Edit' button, but make sure we get
            // the value of the 'plugin type' select.
        '#limit_validation_errors' => array(array('fields', $name, 'type')),
        '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
        '#suffix' => '</div>',
      );

      $entity_type_definition = \Drupal::entityManager()->getDefinition($form['#entity_type']);
      $entity_type_id = $entity_type_definition->id();
      $admin_url = new Url(
        'field_ui.field_group_delete_' . $entity_type_id,
        array_merge($params->route_parameters, array(
          'field_group' => $config_name_suffix,
        ))
      );

      $table[$name]['settings_edit']['#suffix'] .= Drupal::l(t('delete'), $admin_url);
    }

    $form_state->set('field_group', $field_group_form_state);

  }

  // Additional row: add new group.
  $parent_options = $table['#parent_options'];
  unset($parent_options['_add_new_group']);
  $table['_add_new_group'] = field_group_add_row('_add_new_group', $parent_options, $params);

  $table['_add_new_group'] += array(
    'format' => array(
      'type' => array(
        '#type' => 'select',
        '#options' => $formatter_options,
        '#default_value' => 'fieldset',
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
      '#cell_attributes' => array('colspan' => 3),
    ),
  );

  $form['#attached']['library'][] = 'field_group/field_ui';

  $form['#validate'][] = 'field_group_field_overview_validate';
  $form['actions']['submit']['#submit'][] = 'field_group_field_overview_submit';

  // Create the settings for fieldgroup as vertical tabs (merged with DS).
  field_group_field_ui_create_vertical_tabs($form, $form_state, $params);

  // Show a warning if the user has not set up required containers
  if ($form['#fieldgroups']) {

    $parent_requirements = array(
      'multipage' => array(
        'parent' => 'multipage-group',
        'message' => 'Each Multipage element needs to have a parent Multipage group element.',
      ),
      'accordion-item' => array(
        'parent' => 'accordion',
        'message' => 'Each Accordion item element needs to have a parent Accordion group element.',
      ),
    );

    // On display overview tabs need to be checked.
    if (field_group_get_context_from_display($display) == 'view') {
      $parent_requirements['tab'] = array(
        'parent' => 'tabs',
        'message' => 'Each tab element needs to have a parent tabs group element.',
      );
    }

    foreach ($form['#fieldgroups'] as $group_name) {
      $group_check = field_group_load_field_group($group_name, $params->entity_type, $params->bundle, $params->context, $params->mode);
      if (isset($parent_requirements[$group_check->format_type])) {
        if (!$group_check->parent_name || field_group_load_field_group($group_check->parent_name, $params->entity_type, $params->bundle, $params->context, $params->mode)->format_type != $parent_requirements[$group_check->format_type]['parent']) {
          drupal_set_message(t($parent_requirements[$group_check->format_type]['message']), 'warning', FALSE);
        }
      }
    }
  }
}

/**
 * Return an array of field_group_formatter options.
 */
function field_group_field_formatter_options($type) {
  $options = &drupal_static(__FUNCTION__);

  if (!isset($options)) {
    $options = array();

    $manager = \Drupal::service('plugin.manager.field_group.formatters');
    $formatters = $manager->getDefinitions();

    foreach ($formatters as $formatter) {
      if (in_array($type, $formatter['supported_contexts'])) {
        $options[$formatter['id']] = $formatter['label'];
      }
    }
  }

  return $options;
}

/**
 * Helper function to add a row in the overview forms.
 */
function field_group_add_row($name, $parent_options, $params) {

  $display = entity_get_display($params->entity_type, $params->bundle, $params->mode);

  return array(
    '#attributes' => array('class' => array('draggable', 'field-group', 'add-new')),
    '#row_type' => 'add_new_group',
    '#js_settings' => array('rowHandler' => 'group'),
    '#region_callback' => $params->region_callback,
    'label' => array(
      '#title_display' => 'invisible',
      '#title' => t('Label for new group'),
      '#type' => 'textfield',
      '#size' => 15,
      '#description' => t('Label'),
      '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . t('Add new group') . '</div>',
      '#suffix' => '</div>',
    ),
    'weight' => array(
      '#type' => 'textfield',
      '#default_value' => $display->getHighestWeight() + 3,
      '#size' => 3,
      '#title_display' => 'invisible',
      '#title' => t('Weight for new group'),
      '#attributes' => array('class' => array('field-weight')),
      '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
    ),
    'parent_wrapper' => array(
      'parent' => array(
        '#title_display' => 'invisible',
        '#title' => t('Parent for new group'),
        '#type' => 'select',
        '#options' => $parent_options,
        '#empty_value' => '',
        '#attributes' => array('class' => array('field-parent')),
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        '#parents' => array('fields', $name, 'parent'),
      ),
      'hidden_name' => array(
        '#type' => 'hidden',
        '#default_value' => $name,
        '#attributes' => array('class' => array('field-name')),
      ),
    ),
    'group_name' => array(
      '#type' => 'textfield',
      '#title_display' => 'invisible',
      '#title' => t('Machine name for new group'),
      // This field should stay LTR even for RTL languages.
      '#field_prefix' => 'group_',
      '#field_suffix' => '',
      '#attributes' => array('dir' => 'ltr'),
      '#size' => 15,
      '#description' => t('Group name (a-z, 0-9, _)'),
      '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      '#cell_attributes' => array('colspan' => 1),
    ),
  );
}

/**
 * Create vertical tabs.
 */
function field_group_field_ui_create_vertical_tabs(&$form, &$form_state, $params) {

  $form_state->set('field_group_params', $params);

  $existing_group_config = \Drupal::configFactory()->listAll('field_group.' . $params->entity_type . '.' . $params->bundle);
  $displays = array();
  foreach ($existing_group_config as $config) {
    $group = \Drupal::config($config)->get();
    if ($group['context'] == $params->context && $group['mode'] == $params->mode) {
      continue;
    }
    $displays[$group['context'] . '.' . $group['mode']] = $group['context'] . ':' . $group['mode'];
  }

  // No displays to clone.
  if (empty($displays)) {
    return;
  }

  // Add additional settings vertical tab.
  if (!isset($form['additional_settings'])) {
    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
      '#theme_wrappers' => array('vertical_tabs'),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    );
  }

  // Add extra guidelines for webmaster.
  $form['field_group'] = array(
    '#type' => 'details',
    '#group' => 'additional_settings',
    '#title' => t('Fieldgroups'),
    '#description' => t('<p class="fieldgroup-help">Fields can be dragged into groups with unlimited nesting. Each fieldgroup format comes with a configuration form, specific for that format type.<br />Note that some formats come in pair. These types have a html wrapper to nest its fieldgroup children. E.g. Place accordion items into the accordion, vertical tabs in vertical tab group and horizontal tabs in the horizontal tab group. There is one exception to this rule, you can use a vertical tab without a wrapper when the additional settings tabs are available. E.g. node forms.</p>'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['field_group']['fieldgroup_clone'] = array(
    '#title' => t('Select source display'),
    '#description' => t('Clone fieldgroups from selected display to the current display'),
    '#type' => 'select',
    '#options' => $displays,
    '#default_value' => 'none'
  );
  $form['field_group']['fieldgroup_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Clone'),
    '#validate' => array('field_group_field_ui_clone_field_groups_validate'),
    '#submit' => array('field_group_field_ui_clone_field_groups')
  );

}

/**
 * Returns the region to which a row in the 'Manage display' screen belongs.
 * @param Array $row A field or field_group row
 * @return String the current region.
 */
function field_group_display_overview_row_region($row) {
  switch ($row['#row_type']) {
    case 'group':
      return ($row['format']['type']['#value'] == 'hidden' ? 'hidden' : 'content');
    case 'add_new_group':
      // If no input in 'label', assume the row has not been dragged out of the
      // 'add new' section.
      if (empty($row['label']['#value'])) {
        return 'add_new';
      }
      return ($row['format']['type']['#value'] == 'hidden' ? 'hidden' : 'content');
  }
}

/**
 * Validate handler for the overview screens.
 * @param Array $form The complete form.
 * @param FormStateInterface $form_state The state of the form.
 */
function field_group_field_overview_validate($form, FormStateInterface $form_state) {
  $form_values = $form_state->getValue('fields');
  $entity_type = $form['#context']->get('targetEntityType');
  $bundle = $form['#context']->get('bundle');
  $context = field_group_get_context_from_display($form['#context']);
  $mode = $form['#context']->get('mode');

  $group = $form_values['_add_new_group'];

  // Validate if any information was provided in the 'add new group' row.
  if (array_filter(array($group['label'], $group['group_name']))) {

    // Missing group name.
    if (!$group['group_name']) {
      $form_state->setErrorByName('fields][_add_new_group][group_name', t('Add new group: you need to provide a group name.'));
    }
    // Group name validation.
    else {
      $group_name = $group['group_name'];

      // Add the 'group_' prefix.
      if (Unicode::substr($group_name, 0, 6) != 'group_') {
        $group_name = 'group_' . $group_name;
        $form_state->setValueForElement($form['fields']['_add_new_group']['group_name'], $group_name);
      }

      // Invalid group name.
      if (!preg_match('!^group_[a-z0-9_]+$!', $group_name)) {
        $form_state->setErrorByName('fields][_add_new_group][group_name', t('Add new group: the group name %group_name is invalid. The name must include only lowercase unaccentuated letters, numbers, and underscores.', array('%group_name' => $group_name)));
      }
      if (Unicode::strlen($group_name) > 32) {
        $form_state->setErrorByName('fields][_add_new_group][group_name', t("Add new group: the group name %group_name is too long. The name is limited to 32 characters, including the 'group_' prefix.", array('%group_name' => $group_name)));
      }

      // Group name already exists.
      if (field_group_exists($group_name, $entity_type, $bundle, $context, $mode)) {
        $form_state->setErrorByName('fields][_add_new_group][group_name', t('Add new group: the group name %group_name already exists.', array('%group_name' => $group_name)));
      }
    }
  }
}

/**
 * Submit handler for the overview screens.
 * @param Array $form The complete form.
 * @param FormStateInterface $form_state The state of the form.
 */
function field_group_field_overview_submit($form, FormStateInterface $form_state) {
  $form_values = $form_state->getValue('fields');
  $fields_added = $form_state->getValue('fields_added');

  /**
   * @var \Drupal\Core\Entity\EntityDisplayBase $display
   */
  $display = $form['#context'];

  $entity_type = $display->get('targetEntityType');
  $bundle = $display->get('bundle');
  $mode = $display->get('mode');
  $context = field_group_get_context_from_display($display);

  // Collect children.
  $children = array_fill_keys($form['#fieldgroups'], array());
  foreach ($form_values as $name => $value) {
    if (!empty($value['parent'])) {
      // Substitute newly added fields, in case they were dragged
      // directly in a group.
      if ($name == '_add_new_field' && isset($fields_added, $fields_added['_add_new_field'])) {
        $name =  $fields_added['_add_new_field'];
      }
      elseif ($name == '_add_existing_field' && isset($fields_added, $fields_added['_add_existing_field'])) {
        $name = $fields_added['_add_existing_field'];
      }
      $children[$value['parent']][$name] = $name;
    }
  }

  // Create new group.
  if (!empty($form_values['_add_new_group']['group_name'])) {
    $values = $form_values['_add_new_group'];

    $new_group = (object) array(
      'group_name' => $values['group_name'],
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'mode' => $mode,
      'context' => $context,
      'children' => isset($children['_add_new_group']) ? array_keys($children['_add_new_group']) : array(),
      'parent_name' => $values['parent'],
      'weight' => $values['weight'],
      'label' => $values['label'],
      'format_type' => $values['format']['type'],
    );
    $new_group->format_settings = _field_group_get_default_formatter_settings($values['format']['type'], $context);
    field_group_group_save($new_group);

    // Store new group information for any additional submit handlers.
    $groups_added = $form_state->get('groups_added');
    $groups_added['_add_new_group'] = $new_group->group_name;
    drupal_set_message(t('New group %label successfully created.', array('%label' => $new_group->label)));

    // Replace the newly created group in the $children array, in case it was
    // dragged directly in an existing field.
    foreach (array_keys($children) as $parent) {
      if (isset($children[$parent]['_add_new_group'])) {
        unset($children[$parent]['_add_new_group']);
        $children[$parent][$new_group->group_name] = $new_group->group_name;
      }
    }

  }

  // Update existing groups.
  $groups = field_group_info_groups($entity_type, $bundle, $context, $mode, TRUE);
  $field_group_form_state = $form_state->get('field_group');
  if (!empty($field_group_form_state)) {
    foreach ($form['#fieldgroups'] as $group_name) {

      // Only save updated groups.
      if (!isset($field_group_form_state[$group_name])) {
        continue;
      }

      $group = $groups[$group_name];
      $group->label = $field_group_form_state[$group_name]->label;
      $group->children = array_keys($children[$group_name]);
      $group->parent_name = $form_values[$group_name]['parent'];
      $group->weight = $form_values[$group_name]['weight'];

      $old_format_type = $group->format_type;
      $group->format_type = isset($form_values[$group_name]['format']['type']) ? $form_values[$group_name]['format']['type'] : 'visible';
      if (isset($field_group_form_state[$group_name]->format_settings)) {
        $group->format_settings = $field_group_form_state[$group_name]->format_settings;
      }

      // If the format type is changed, make sure we have all required format settings.
      if ($group->format_type != $old_format_type) {
        $default_formatter_settings = _field_group_get_default_formatter_settings($group->format_type, $context);
        $group->format_settings += $default_formatter_settings;
      }

      field_group_group_save($group);
    }
  }

  \Drupal::cache()->invalidate('field_groups');
}

/**
 * Creates a form for field_group formatters.
 * @param Object $group The FieldGroup object.
 */
function field_group_format_settings_form(&$group, $form, $form_state) {
  $manager = \Drupal::service('plugin.manager.field_group.formatters');
  $plugin = $manager->getInstance(array(
    'format_type' => $group->format_type,
    'configuration' => array('label' => $group->label, 'settings' => $group->format_settings),
    'group' => $group,
  ));

  return $plugin->settingsForm($form, $form_state);
}

/**
 * Validate the entered css class from the submitted format settings.
 * @param Array $element The validated element
 * @param FormStateInterface $form_state The state of the form.
 */
function field_group_validate_css_class($element, FormStateInterface $form_state) {
  $form_state_values = $form_state->getValues();
  $plugin_name = $form_state->get('plugin_settings_edit');
  if (!empty($form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['classes']) && !preg_match('!^[A-Za-z0-9-_ ]+$!', $form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['classes'])) {
    \Drupal::formBuilder()->setError($element, $form_state, t('The css class must include only letters, numbers, underscores and dashes.'));
  }
}

/**
 * Validate the entered id attribute from the submitted format settings.
 * @param Array $element The validated element
 * @param FormStateInterface $form_state The state of the form.
 */
function field_group_validate_id($element, FormStateInterface $form_state) {
  $form_state_values = $form_state->getValues();
  $plugin_name = $form_state->get('plugin_settings_edit');
  if (!empty($form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['id']) && !preg_match('!^[A-Za-z0-9-_]+$!', $form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['id'])) {
    \Drupal::formBuilder()->setError($element, $form_state, t('The id must include only letters, numbers, underscores and dashes.'));
  }
}

/**
 * Update the row so that the group variables are updated.
 * The rendering of the elements needs the updated defaults.
 * @param Object $group
 * @param array $settings
 */
function field_group_formatter_row_update(& $group, $settings) {
  // if the row has changed formatter type, update the group object
  if (!empty($settings['format']['type']) && $settings['format']['type'] != $group->format_type) {
    $group->format_type = $settings['format']['type'];
    field_group_formatter_settings_update($group, $settings);
  }
}

/**
 * Update handler for field_group configuration settings.
 * @param Object $group The group object
 * @param Array $settings Configuration settings
 */
function field_group_formatter_settings_update(& $group, $settings) {

  // for format changes we load the defaults.
  if (empty($settings['settings_edit_form']['settings'])) {
    $group->format_settings = _field_group_get_default_formatter_settings($group->format_type, $group->context);
  }
  else {
    $group->format_type = $settings['format']['type'];
    $group->label = $settings['settings_edit_form']['settings']['label'];
    $group->format_settings = $settings['settings_edit_form']['settings'];
  }
}

/**
 * Creates a summary for the field format configuration summary.
 * @param String $group_name The name of the group
 * @param Object $group The group object
 * @return Array ready to be rendered.
 */
function field_group_format_settings_summary($group_name, $group) {

  $manager = \Drupal::service('plugin.manager.field_group.formatters');
  $plugin = $manager->getInstance(array(
    'format_type' => $group->format_type,
    'configuration' => array('label' => $group->label, 'settings' => $group->format_settings),
    'group' => $group,
  ));

  $summary = $plugin->settingsSummary();

  return array(
    '#markup' => '<div class="field-plugin-summary">' . implode('<br />', $summary) . '</div>',
    '#cell_attributes' => array('class' => array('field-plugin-summary-cell')),
  );
}

/**
 * Validate handler to validate saving existing fieldgroups from one view mode or form to another.
 */
function field_group_field_ui_clone_field_groups_validate($form, FormStateInterface $form_state) {

  $form_state_values = $form_state->getValues();
  $field_group_params = $form_state->get('field_group_params');

  list($context, $mode) = explode('.', $form_state_values['fieldgroup_clone']);
  $source_groups = field_group_info_groups($field_group_params->entity_type, $field_group_params->bundle, $context, $mode);

  // Check for types are not known in current mode.
  if ($field_group_params->context != 'form') {
    $non_existing_types = array('multipage', 'multipage-group');
  }
  else {
    $non_existing_types = array('html_element');
  }

  foreach ($source_groups as $key => $group) {
    if (in_array($group->format_type, $non_existing_types)) {
      unset($source_groups[$key]);
      drupal_set_message(t('Skipping @group because this type does not exist in current mode', array('@group' => $group->label)), 'warning');
    }
  }

  if (empty($source_groups)) {
    // Report error found with selection.
    $form_state->setErrorByName('additional_settings][fieldgroup_clone', t('No field groups were found in selected view mode.'));
    return;
  }

  $form_state->set('#source_groups', $source_groups);

}

/**
 * Submit handler to save existing fieldgroups from one view mode or form to another.
 */
function field_group_field_ui_clone_field_groups($form, FormStateInterface $form_state) {

  $fields = array_keys($form_state->getValue('fields'));
  $source_groups = $form_state->get('#source_groups');
  if ($source_groups) {

    $field_group_params = $form_state->get('field_group_params');
    foreach ($source_groups as $source_group) {
      if (in_array($source_group->group_name, $fields)) {
        drupal_set_message(t('Fieldgroup @group is not cloned since a group already exists with the same name.', array('@group' => $source_group->group_name)), 'warning');
        continue;
      }

      $source_group->context = $field_group_params->context;
      $source_group->mode = $field_group_params->mode;
      $source_group->children = array();
      field_group_group_save($source_group);

      drupal_set_message(t('Fieldgroup @group cloned successfully.', array('@group' => $source_group->group_name)));

    }
  }

}
