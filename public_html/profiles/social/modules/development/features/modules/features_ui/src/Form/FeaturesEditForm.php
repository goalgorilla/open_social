<?php

/**
 * @file
 * Contains \Drupal\features_ui\Form\FeaturesEditForm.
 */

namespace Drupal\features_ui\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesGeneratorInterface;
use Drupal\features\FeaturesManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the features settings form.
 */
class FeaturesEditForm extends FormBase {

  /**
   * The features manager.
   *
   * @var array
   */
  protected $featuresManager;

  /**
   * The package assigner.
   *
   * @var array
   */
  protected $assigner;

  /**
   * The package generator.
   *
   * @var array
   */
  protected $generator;

  /**
   * Current package array being edited.
   *
   * @var array
   */
  protected $package;

  /**
   * Current bundle machine name.
   *
   * NOTE: D8 cannot serialize objects within forms so you can't directly
   * store the entire Bundle object here.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Previous bundle name for ajax processing.
   *
   * @var string
   */
  protected $oldBundle;

  /**
   * Config to be specifically excluded.
   *
   * @var array
   */
  protected $excluded;

  /**
   * Config to be specifically required.
   *
   * @var array
   */
  protected $required;

  /**
   * Config referenced by other packages.
   *
   * @var array
   */
  protected $conflicts;

  /**
   * Determine if conflicts are allowed to be added.
   *
   * @var bool
   */
  protected $allowConflicts;

  /**
   * Constructs a FeaturesEditForm object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager.
   */
  public function __construct(FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner, FeaturesGeneratorInterface $generator) {
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
    $this->generator = $generator;
    $this->excluded = [];
    $this->required = [];
    $this->conflicts = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('features.manager'),
      $container->get('features_assigner'),
      $container->get('features_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'features_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $featurename = '') {
    $session = \Drupal::request()->getSession();
    $this->allowConflicts = FALSE;
    if (isset($session)) {
      $this->allowConflicts = $session->get('features_allow_conflicts', FALSE);
    }

    $trigger = $form_state->getTriggeringElement();
    if ($trigger['#name'] == 'package') {
      $this->oldBundle = $this->bundle;
      $bundle_name = $form_state->getValue('package');
      $bundle = $this->assigner->getBundle($bundle_name);
    }
    elseif ($trigger['#name'] == 'conflicts') {
      if (isset($session)) {
        $session->set('features_allow_conflicts', $form_state->getValue('conflicts'));
      }
      return $this->redirect('features.edit', array($featurename));
    }
    else {
      $bundle = $this->assigner->loadBundle();
    }
    $this->bundle = $bundle->getMachineName();

    // Pass the $force argument as TRUE because we want to include any excluded
    // configuration items. These should show up as automatically assigned, but
    // not selected, thus allowing the admin to reselect if desired.
    // @see FeaturesManagerInterface::assignConfigPackage()
    $this->assigner->assignConfigPackages(TRUE);

    $packages = $this->featuresManager->getPackages();
    if (empty($packages[$featurename])) {
      $featurename = str_replace(array('-', ' '), '_', $featurename);
      $this->package = $this->featuresManager->initPackage($featurename, NULL, '', 'module', $bundle);
    }
    else {
      $this->package = $packages[$featurename];
    }

    $form = array(
      '#show_operations' => FALSE,
      '#prefix' => '<div id="features-edit-wrapper">',
      '#suffix' => '</div>',
    );

    $form['info'] = array(
      '#type' => 'fieldset',
      '#title' => t('General Information'),
      '#tree' => FALSE,
      '#weight' => 2,
      '#prefix' => "<div id='features-export-info'>",
      '#suffix' => '</div>',
    );

    $form['info']['name'] = array(
      '#title' => t('Name'),
      '#description' => t('Example: Image gallery') . ' (' . t('Do not begin name with numbers.') . ')',
      '#type' => 'textfield',
      '#default_value' => $this->package['name'],
    );
    if (!$bundle->isDefault()) {
      $form['info']['name']['#description'] .= '<br/>' .
        t('The namespace "@name_" will be prepended to the machine name', array('@name' => $bundle->getMachineName()));
    }

    $form['info']['machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine-readable name'),
      '#description' => t('Example: image_gallery') . ' ' . t('May only contain lowercase letters, numbers and underscores.'),
      '#required' => TRUE,
      '#default_value' => $bundle->getShortName($this->package['machine_name']),
      '#machine_name' => array(
        'source' => array('info', 'name'),
        'exists' => array($this, 'featureExists'),
      ),
    );
    if (!$bundle->isDefault()) {
      $form['info']['machine_name']['#description'] .= '<br/>' .
        t('NOTE: Do NOT include the namespace prefix "@name_"; it will be added automatically.', array('@name' => $bundle->getMachineName()));
    }

    $form['info']['description'] = array(
      '#title' => t('Description'),
      '#description' => t('Provide a short description of what users should expect when they enable your feature.'),
      '#type' => 'textarea',
      '#rows' => 3,
      '#default_value' => $this->package['description'],
    );

    $form['info']['package'] = array(
      '#title' => t('Bundle'),
      '#type' => 'select',
      '#options' => $this->assigner->getBundleOptions(t('--None--')),
      '#default_value' => $bundle->getMachineName(),
      '#ajax' => array(
        'callback' => '::updateBundle',
        'wrapper' => 'features-export-info',
      ),
    );

    $form['info']['version'] = array(
      '#title' => t('Version'),
      '#description' => t('Examples: 8.x-1.0, 8.x-1.0-beta1'),
      '#type' => 'textfield',
      '#required' => FALSE,
      '#default_value' => $this->package['version'],
      '#size' => 30,
    );

    $form['conflicts'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow conflicts'),
      '#default_value' => $this->allowConflicts,
      '#description' => $this->t('Allow configuration to be exported to more than one feature.'),
      '#weight' => 8,
      '#ajax' => array(
        'callback' => '::updateForm',
        'wrapper' => 'features-edit-wrapper',
      ),
    );

    $generation_info = array();
    if (\Drupal::currentUser()->hasPermission('export configuration')) {
      // Offer available generation methods.
      $generation_info = $this->generator->getGenerationMethods();
      // Sort generation methods by weight.
      uasort($generation_info, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    }

    $form['actions'] = array('#type' => 'actions', '#tree' => TRUE);
    foreach ($generation_info as $method_id => $method) {
      $form['actions'][$method_id] = array(
        '#type' => 'submit',
        '#name' => $method_id,
        '#value' => $this->t('@name', array('@name' => $method['name'])),
        '#attributes' => array(
          'title' => SafeMarkup::checkPlain($method['description']),
        ),
      );
    }

    // Build the Component Listing panel on the right.
    $form['export'] = $this->buildComponentList($form_state);

    $form['#attached'] = array(
      'library' => array(
        'features_ui/drupal.features_ui.admin',
      ),
      'drupalSettings' => array(
        'features' => array(
          'excluded' => $this->excluded,
          'required' => $this->required,
          'conflicts' => $this->conflicts,
          'autodetect' => TRUE,
        ),
      ),
    );

    return $form;
  }

  /**
   * Provides an ajax callback for handling conflict checkbox.
   */
  public function updateForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Provides an ajax callback for handling switching the bundle selector.
   */
  public function updateBundle($form, FormStateInterface $form_state) {
    $old_bundle = $this->assigner->getBundle($this->oldBundle);
    $bundle_name = $form_state->getValue('package');
    $bundle = $this->assigner->getBundle($bundle_name);
    if (isset($bundle) && isset($old_bundle)) {
      $short_name = $old_bundle->getShortName($this->package['machine_name']);
      if ($bundle->isDefault()) {
        $short_name = $old_bundle->getFullName($short_name);
      }
      $this->package['machine_name'] = $bundle->getFullName($short_name);
      $form['info']['machine_name']['#value'] = $bundle->getShortName($this->package['machine_name']);
    }
    return $form['info'];
  }

  /**
   * Callback for machine_name exists()
   * @param $value
   * @param $element
   * @param $form_state
   * @return bool
   */
  public function featureExists($value, $element, $form_state) {
    $packages = $this->featuresManager->getPackages();
    return isset($packages[$value]) || \Drupal::moduleHandler()->moduleExists($value);
  }

  /**
   * Returns the render array elements for the Components selection on the Edit
   * form.
   */
  protected function buildComponentList(FormStateInterface $form_state) {
    $element = array(
      '#type' => 'fieldset',
      '#title' => t('Components'),
      '#description' => t('Expand each component section and select which items should be included in this feature export.'),
      '#tree' => FALSE,
      '#prefix' => "<div id='features-export-wrapper'>",
      '#suffix' => '</div>',
      '#weight' => 1,
    );

    // Filter field used in javascript, so javascript will unhide it.
    $element['features_filter_wrapper'] = array(
      '#type' => 'fieldset',
      '#title' => t('Filters'),
      '#tree' => FALSE,
      '#prefix' => "<div id='features-filter' class='element-invisible'>",
      '#suffix' => '</div>',
      '#weight' => -10,
    );
    $element['features_filter_wrapper']['features_filter'] = array(
      '#type' => 'textfield',
      '#title' => t('Search'),
      '#hidden' => TRUE,
      '#default_value' => '',
      '#suffix' => "<span class='features-filter-clear'>" . t('Clear') . "</span>",
    );
    $element['features_filter_wrapper']['checkall'] = array(
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#hidden' => TRUE,
      '#title' => t('Select all'),
      '#attributes' => array(
        'class' => array('features-checkall'),
      ),
    );

    $sections = array('included', 'detected', 'added');
    $config_types = $this->featuresManager->listConfigTypes();

    // Generate the export array for the current feature and user selections.
    $export = $this->getComponentList($form_state);

    foreach ($export['components'] as $component => $component_info) {

      $component_items_count = count($component_info['options']['sources']);
      $label = SafeMarkup::format('@component (<span class="component-count">@count</span>)',
        array(
          '@component' => $config_types[$component],
          '@count' => $component_items_count,
        )
      );

      $count = 0;
      foreach ($sections as $section) {
        $count += count($component_info['options'][$section]);
      }
      $extra_class = ($count == 0) ? 'features-export-empty' : '';
      $component_name = str_replace('_', '-', SafeMarkup::checkPlain($component));

      if ($count + $component_items_count > 0) {
        $element[$component] = array(
          '#markup' => '',
          '#tree' => TRUE,
        );

        $element[$component]['sources'] = array(
          '#type' => 'details',
          '#title' => $label,
          '#tree' => TRUE,
          '#open' => FALSE,
          '#attributes' => array('class' => array('features-export-component')),
          '#prefix' => "<div class='features-export-parent component-$component'>",
        );
        $element[$component]['sources']['selected'] = array(
          '#type' => 'checkboxes',
          '#id' => "edit-sources-$component_name",
          '#options' => $this->domDecodeOptions($component_info['options']['sources']),
          '#default_value' => $this->domDecodeOptions($component_info['selected']['sources'], FALSE),
          '#attributes' => array('class' => array('component-select')),
          '#prefix' => "<span class='component-select'>",
          '#suffix' => '</span>',
        );

        $element[$component]['before-list'] = array(
          '#markup' => "<div class='component-list features-export-list $extra_class'>",
        );

        foreach ($sections as $section) {
          $element[$component][$section] = array(
            '#type' => 'checkboxes',
            '#options' => !empty($component_info['options'][$section]) ?
              $this->domDecodeOptions($component_info['options'][$section]) : array(),
            '#default_value' => !empty($component_info['selected'][$section]) ?
              $this->domDecodeOptions($component_info['selected'][$section], FALSE) : array(),
            '#attributes' => array('class' => array('component-' . $section)),
            '#prefix' => "<span class='component-$section'>",
            '#suffix' => '</span>',
          );
        }

        // Close both the before-list as well as the sources div.
        $element[$component]['after-list'] = array(
          '#markup' => "</div></div>",
        );
      }
    }
    $element['features_legend'] = array(
      '#type' => 'fieldset',
      '#title' => t('Legend'),
      '#tree' => FALSE,
      '#prefix' => "<div id='features-legend'>",
      '#suffix' => '</div>',
    );
    $element['features_legend']['legend'] = array(
      '#markup' =>
        "<span class='component-included'>" . t('Normal') . "</span> " .
        "<span class='component-added'>" . t('Added') . "</span> " .
        "<span class='component-detected'>" . t('Auto detected') . "</span> " .
        "<span class='component-conflict'>" . t('Conflict') . "</span> ",
    );

    return $element;
  }

  /**
   * Returns the full feature export array based upon user selections in
   * form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Optional form_state information for user selections. Can be updated to
   *   reflect new selection status.
   *
   * @return array
   *   New export array to be exported
   *   array['components'][$component_name] = $component_info
   *     $component_info['options'][$section] is list of available options
   *     $component_info['selected'][$section] is option state TRUE/FALSE
   *   $section = array('sources', included', 'detected', 'added')
   *     sources - options that are available to be added to the feature
   *     included - options that have been previously exported to the feature
   *     detected - options that have been auto-detected
   *     added - newly added options to the feature
   *
   * NOTE: This routine gets a bit complex to handle all of the different
   * possible user checkbox selections and de-selections.
   * Cases to test:
   *   1a) uncheck Included item -> mark as Added but unchecked
   *   1b) re-check unchecked Added item -> return it to Included check item
   *   2a) check Sources item -> mark as Added and checked
   *   2b) uncheck Added item -> return it to Sources as unchecked
   *   3a) uncheck Included item that still exists as auto-detect -> mark as
   *       Detected but unchecked
   *   3b) re-check Detected item -> return it to Included and checked
   *   4a) check Sources item should also add any auto-detect items as Detected
   *       and checked
   *   4b) uncheck Sources item with auto-detect and auto-detect items should
   *       return to Sources and unchecked
   *   5a) uncheck a Detected item -> refreshing page should keep it as
   *       unchecked Detected
   *   6)  when nothing changes, refresh should not change any state
   *   7)  should never see an unchecked Included item
   */
  protected function getComponentList(FormStateInterface $form_state) {
    $config = $this->featuresManager->getConfigCollection();

    $package_name = $this->package['machine_name'];
    // Auto-detect dependencies for included config.
    $package_config = !empty($this->package['config']) ? $this->package['config'] : array();
    if (!empty($this->package['config_orig'])) {
      $package_config = array_unique(array_merge($package_config, $this->package['config_orig']));
    }
    if (!empty($package_config)) {
      $this->featuresManager->assignConfigDependents($package_config, $package_name);
    }

    $packages = $this->featuresManager->getPackages();
    // Re-fetch the package in case config was updated with Dependents above.
    $this->package = $packages[$package_name];

    // Make a map of all config data.
    $components = array();
    $this->conflicts = array();
    foreach ($config as $item_name => $item) {
      if (($item->getPackage() != $package_name) &&
        !empty($packages[$item->getPackage()]) && ($packages[$item->getPackage()]['status'] != FeaturesManagerInterface::STATUS_NO_EXPORT)) {
        $this->conflicts[$item->getType()][$item->getShortName()] = $item->getLabel();
      }
      if ($this->allowConflicts
        || !isset($this->conflicts[$item->getType()][$item->getShortName()])
        || (!empty($this->package['config_orig']) && in_array($item_name, $this->package['config_orig']))) {
        $components[$item->getType()][$item->getShortName()] = $item->getLabel();
      }
    }

    // Make a map of the config data already exported to the Feature.
    $exported_features_info = array();
    if (!empty($this->package['config_orig'])) {
      foreach ($this->package['config_orig'] as $item_name) {
        // Make sure the extension provided item exists in the active
        // configuration storage.
        if (isset($config[$item_name])) {
          $item = $config[$item_name];
          // Remove any conflicts if those are not being allowed.
          // if ($this->allowConflicts || !isset($this->conflicts[$item['type']][$item['name_short']])) {
          $exported_features_info[$item->getType()][$item->getShortName()] = $item->getLabel();
          // }
        }
      }
    }
    $exported_features_info['dependencies'] = !empty($this->package['info']['dependencies']) ? $this->package['info']['dependencies'] : array();

    // Make a map of any config specifically excluded and/or required.
    foreach (array('excluded', 'required') as $constraint) {
      $this->{$constraint} = array();
      $info = !empty($this->package['info']['features'][$constraint]) ? $this->package['info']['features'][$constraint] : array();
      foreach ($info as $item_name) {
        $item = $config[$item_name];
        $this->{$constraint}[$item->getType()][$item->getShortName()] = $item->getLabel();
      }
    }

    // Make a map of the config data to be exported within the Feature.
    $new_features_info = array();
    foreach ($this->package['config'] as $item_name) {
      $item = $config[$item_name];
      $new_features_info[$item->getType()][$item->getShortName()] = $item->getLabel();
    }
    $new_features_info['dependencies'] = !empty($this->package['dependencies']) ? $this->package['dependencies'] : array();

    // Assemble the combined component list.
    $config_new = array();
    $sections = array('sources', 'included', 'detected', 'added');

    // Generate list of config to be exported.
    $config_count = array();
    foreach ($components as $component => $component_info) {
      // User-selected components take precedence.
      $config_new[$component] = array();
      $config_count[$component] = 0;
      // Add selected items from Sources checkboxes.
      if (!$form_state->isValueEmpty(array($component, 'sources', 'selected'))) {
        $config_new[$component] = array_merge($config_new[$component], $this->domDecodeOptions(array_filter($form_state->getValue(array(
          $component,
          'sources',
          'selected',
        )))));
        $config_count[$component]++;
      }
      // Add selected items from already Included, newly Added, auto-detected
      // checkboxes.
      foreach (array('included', 'added', 'detected') as $section) {
        if (!$form_state->isValueEmpty(array($component, $section))) {
          $config_new[$component] = array_merge($config_new[$component], $this->domDecodeOptions(array_filter($form_state->getValue(array($component, $section)))));
          $config_count[$component]++;
        }
      }
      // Only fallback to an existing feature's values if there are no export
      // options for the component.
      if ($component == 'dependencies') {
        if (($config_count[$component] == 0) && !empty($exported_features_info['dependencies'])) {
          $config_new[$component] = array_combine($exported_features_info['dependencies'], $exported_features_info['dependencies']);
        }
      }
      elseif (($config_count[$component] == 0) && !empty($exported_features_info[$component])) {
        $config_names = array_keys($exported_features_info[$component]);
        $config_new[$component] = array_combine($config_names, $config_names);
      }
    }

    // Generate new populated feature.
    $export = $this->package;
    $export['config_new'] = $config_new;

    // Now fill the $export with categorized sections of component options
    // based upon user selections and de-selections.
    foreach ($components as $component => $component_info) {
      $component_export = $component_info;
      foreach ($sections as $section) {
        $component_export['options'][$section] = array();
        $component_export['selected'][$section] = array();
      }
      if (!empty($component_info)) {
        $exported_components = !empty($exported_features_info[$component]) ? $exported_features_info[$component] : array();
        $new_components = !empty($new_features_info[$component]) ? $new_features_info[$component] : array();

        foreach ($component_info as $key => $label) {
          $config_name = $this->featuresManager->getFullName($component, $key);
          // If checkbox in Sources is checked, move it to Added section.
          if (!$form_state->isValueEmpty(array($component, 'sources', 'selected', $key))) {
            $form_state->setValue(array($component, 'sources', 'selected', $key), FALSE);
            $form_state->setValue(array($component, 'added', $key), 1);
            $component_export['options']['added'][$key] = $this->configLabel($component, $key, $label);
            $component_export['selected']['added'][$key] = $key;
            // If this was previously excluded, we don't need to set it as
            // required because it was automatically assigned.
            if (isset($this->excluded[$component][$key])) {
              unset($this->excluded[$component][$key]);
            }
            else {
              $this->required[$component][$key] = $key;
            }
          }
          elseif (isset($new_components[$key])) {
            // Option is in the New exported array.
            if (isset($exported_components[$key])) {
              // Option was already previously exported so it's part of the
              // Included checkboxes.
              $section = 'included';
              $default_value = $key;
              // If Included item was un-selected (removed from export
              // $config_new) but was re-detected in the $new_components
              // means it was an auto-detect that was previously part of the
              // export and is now de-selected in UI.
              if ($form_state->isSubmitted() &&
                  ($form_state->hasValue(array($component, 'included', $key)) ||
                  ($form_state->isValueEmpty(array($component, 'detected', $key)))) &&
                  empty($config_new[$component][$key])) {
                $section = 'detected';
                $default_value = FALSE;
              }
              // Unless it's unchecked in the form, then move it to Newly
              // disabled item.
              elseif ($form_state->isSubmitted() &&
                  $form_state->isValueEmpty(array($component, 'added', $key)) &&
                  $form_state->isValueEmpty(array($component, 'detected', $key)) &&
                  $form_state->isValueEmpty(array($component, 'included', $key))) {
                $section = 'added';
                $default_value = FALSE;
              }
            }
            else {
              // Option was in New exported array, but NOT in already exported
              // so it's a user-selected or an auto-detect item.
              $section = 'detected';
              $default_value = NULL;
              // Check for item explicitly excluded.
              if (isset($this->excluded[$component][$key]) && !$form_state->hasValue(array($component, 'detected', $key))) {
                $default_value = FALSE;
              }
              else {
                $default_value = $key;
              }
              // If it's already checked in Added or Sources, leave it in Added
              // as checked.
              if ($form_state->isSubmitted() &&
                  (!$form_state->isValueEmpty(array($component, 'added', $key)) ||
                   !$form_state->isValueEmpty(array($component, 'sources', 'selected', $key)))) {
                $section = 'added';
                $default_value = $key;
              }
              // If it's already been unchecked, leave it unchecked.
              elseif ($form_state->isSubmitted() &&
                  $form_state->isValueEmpty(array($component, 'sources', 'selected', $key)) &&
                  $form_state->isValueEmpty(array($component, 'detected', $key)) &&
                  !$form_state->hasValue(array($component, 'added', $key))) {
                $section = 'detected';
                $default_value = FALSE;
              }
            }
            $component_export['options'][$section][$key] = $this->configLabel($component, $key, $label);
            $component_export['selected'][$section][$key] = $default_value;
            // Save which dependencies are specifically excluded from
            // auto-detection.
            if (($section == 'detected') && ($default_value === FALSE)) {
              // If this was previously required, we don't need to set it as
              // excluded because it wasn't automatically assigned.
              if (isset($this->required[$component][$key])) {
                unset($this->required[$component][$key]);
              }
              else {
                $this->excluded[$component][$key] = $key;
              }
              // Remove excluded item from export.
              if ($component == 'dependencies') {
                unset($export['dependencies'][$key]);
              }
              else {
                unset($export['config'][$config_name]);
              }
            }
            else {
              unset($this->excluded[$component][$key]);
            }
            // Remove the 'input' and set the 'values' so Drupal stops looking
            // at 'input'.
            if ($form_state->isSubmitted()) {
              if (!$default_value) {
                $form_state->setValue(array($component, $section, $key), FALSE);
              }
              else {
                $form_state->setValue(array($component, $section, $key), 1);
              }
            }
          }
          elseif (!$form_state->isSubmitted() && isset($exported_components[$key])) {
            // Component is not part of new export, but was in original export.
            // Mark component as Added when creating initial form.
            $component_export['options']['added'][$key] = $this->configLabel($component, $key, $label);
            $component_export['selected']['added'][$key] = $key;
          }
          else {
            // Option was not part of the new export.
            $added = FALSE;
            foreach (array('included', 'added') as $section) {
              // Restore any user-selected checkboxes.
              if (!$form_state->isValueEmpty(array($component, $section, $key))) {
                $component_export['options'][$section][$key] = $this->configLabel($component, $key, $label);
                $component_export['selected'][$section][$key] = $key;
                $added = TRUE;
              }
            }
            if (!$added) {
              // If not Included or Added, then put it back in the unchecked
              // Sources checkboxes.
              $component_export['options']['sources'][$key] = $this->configLabel($component, $key, $label);
              $component_export['selected']['sources'][$key] = FALSE;
            }
          }
        }
      }
      $export['components'][$component] = $component_export;
    }
    $export['features_exclude'] = $this->excluded;
    $export['features_require'] = $this->required;
    $export['conflicts'] = $this->conflicts;

    return $export;
  }

  /**
   * Returns a formatted and sanitized label for a config item.
   *
   * @param string $type
   *   The config type.
   * @param string $key
   *   The short machine name of the item.
   * @param string $label
   *   The human label for the item.
   */
  protected function configLabel($type, $key, $label) {
    $value = SafeMarkup::checkPlain($label);
    if ($key != $label) {
      $value .= '  <span class="config-name">(' . SafeMarkup::checkPlain($key) . ')</span>';
    }
    if (isset($this->conflicts[$type][$key])) {
      // Show what package the conflict is stored in.
      // Get the full machine name instead of the short name.
      $packages = $this->featuresManager->getPackages();
      $package_name = $this->conflicts[$type][$key]['package'];
      if (isset($packages[$package_name])) {
        $package_name = $packages[$package_name]['machine_name'];
      }
      $value .= '  <span class="config-name">[' . t('in') . ' ' . SafeMarkup::checkPlain($package_name) . ']</span>';
    }
    return Xss::filterAdmin($value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundle = $this->assigner->getBundle($this->bundle);
    $this->assigner->assignConfigPackages();

    $this->package['name'] = $form_state->getValue('name');
    $this->package['machine_name'] = $bundle->getFullName($form_state->getValue('machine_name'));
    $this->package['description'] = $form_state->getValue('description');
    $this->package['version'] = $form_state->getValue('version');
    $this->package['bundle'] = $bundle->getMachineName();
    // Save it first just to create it in case it's a new package.
    $this->featuresManager->setPackage($this->package);

    $this->package['config'] = $this->updatePackageConfig($form_state);
    $this->package['excluded'] = $this->updateExcluded();
    $this->package['required'] = $this->updateRequired();
    // Now save it with the selected config data.
    $this->featuresManager->setPackage($this->package);

    $method_id = NULL;
    $trigger = $form_state->getTriggeringElement();
    $op = $form_state->getValue('op');
    if (!empty($trigger) && empty($op)) {
      $method_id = $trigger['#name'];
    }

    // Set default redirect, but allow generators to change it later.
    $form_state->setRedirect('features.edit', array('featurename' => $this->package['machine_name']));
    if (!empty($method_id)) {
      $packages = array($this->package['machine_name']);
      $this->generator->generatePackages($method_id, $packages, $bundle);
      $this->generator->applyExportFormSubmit($method_id, $form, $form_state);
    }

    $this->assigner->setCurrent($bundle);
  }

  /**
   * Updates the config stored in the package from the current edit form.
   *
   * @return array
   *   Config array to be exported.
   */
  protected function updatePackageConfig(FormStateInterface $form_state) {
    $config = array();
    $components = $this->getComponentList($form_state);
    foreach ($components['config_new'] as $config_type => $items) {
      foreach ($items as $name) {
        $config[] = $this->featuresManager->getFullName($config_type, $name);
      }
    }
    return $config;
  }

  /**
   * Updates the list of excluded config.
   *
   * @return array
   *   The list of excluded config in a simple array of full config names
   *   suitable for storing in the info.yml file.
   */
  protected function updateExcluded() {
    return $this->updateConstrained('excluded');
  }

  /**
   * Updates the list of required config.
   *
   * @return array
   *   The list of required config in a simple array of full config names
   *   suitable for storing in the info.yml file.
   */
  protected function updateRequired() {
    return $this->updateConstrained('required');
  }

  /**
   * Returns a list of constrained (excluded or required) configuration.
   *
   * @param string $constraint
   *   The constraint (excluded or required).
   * @return array
   *   The list of constrained config in a simple array of full config names
   *   suitable for storing in the info.yml file.
   */
  protected function updateConstrained($constraint) {
    $constrained = array();
    foreach ($this->{$constraint} as $type => $item) {
      foreach ($item as $name => $value) {
        $constrained[] = $this->featuresManager->getFullName($type, $name);
      }
    }
    return $constrained;
  }

  /**
   * Encodes a given key.
   *
   * @param string $key
   *   The key to encode.
   *
   * @return string
   *   The encoded key.
   */
  protected function domEncode($key) {
    $replacements = $this->domEncodeMap();
    return strtr($key, $replacements);
  }

  /**
   * Decodes a given key.
   *
   * @param string $key
   *   The key to decode.
   *
   * @return string
   *   The decoded key.
   */
  protected function domDecode($key) {
    $replacements = array_flip($this->domEncodeMap());
    return strtr($key, $replacements);
  }

  /**
   * Decodes an array of option values that have been encoded by
   * features_dom_encode_options().
   *
   * @param array $options
   *   The key to encode.
   * @param bool $keys_only
   *   Whether to decode only the keys.
   *
   * @return array
   *   An array of encoded options.
   */
  protected function domDecodeOptions(array $options, $keys_only = FALSE) {
    $replacements = array_flip($this->domEncodeMap());
    $encoded = array();
    foreach ($options as $key => $value) {
      $encoded[strtr($key, $replacements)] = $keys_only ? $value : strtr($value, $replacements);
    }
    return $encoded;
  }

  /**
   * Returns encoding map for decode and encode options.
   *
   * @return array
   *   An encoding map.
   */
  protected function domEncodeMap() {
    return array(
      ':' => '__' . ord(':') . '__',
      '/' => '__' . ord('/') . '__',
      ',' => '__' . ord(',') . '__',
      '.' => '__' . ord('.') . '__',
      '<' => '__' . ord('<') . '__',
      '>' => '__' . ord('>') . '__',
      '%' => '__' . ord('%') . '__',
      ')' => '__' . ord(')') . '__',
      '(' => '__' . ord('(') . '__',
    );
  }

}
