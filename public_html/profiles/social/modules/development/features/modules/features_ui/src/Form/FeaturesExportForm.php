<?php

/**
 * @file
 * Contains \Drupal\features_ui\Form\FeaturesExportForm.
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
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Defines the configuration export form.
 */
class FeaturesExportForm extends FormBase {

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
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a FeaturesExportForm object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *    The features manager.
   * @param \Drupal\features\FeaturesAssignerInterface $features_assigner
   *    The features assigner.
   * @param \Drupal\features\FeaturesGeneratorInterface $features_generator
   *    The features generator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *    The features generator.
   */
  public function __construct(FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner, FeaturesGeneratorInterface $generator, ModuleHandlerInterface $module_handler) {
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
    $this->generator = $generator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('features.manager'),
      $container->get('features_assigner'),
      $container->get('features_generator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'features_export_form';
  }

  /**
   * Detects if an element triggered the form submission via Ajax.
   * TODO: SHOULDN'T NEED THIS!  BUT DRUPAL IS CALLING buildForm AFTER THE
   * BUNDLE AJAX IS SELECTED AND DOESN'T HAVE getTriggeringElement() SET YET.
   */
  protected function elementTriggeredScriptedSubmission(FormStateInterface &$form_state) {
    $input = $form_state->getUserInput();
    if (!empty($input['_triggering_element_name'])) {
      return $input['_triggering_element_name'];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $trigger = $form_state->getTriggeringElement();
    // TODO: See if there is a Drupal Core issue for this.
    // Sometimes the first ajax call on the page causes buildForm to be called
    // twice!  First time form_state->getTriggeringElement is NOT SET, but
    // the form_state['input'] shows the _triggering_element_name.  Then the
    // SECOND time it is called the getTriggeringElement is fine.
    $real_trigger = $this->elementTriggeredScriptedSubmission($form_state);
    if (!isset($trigger) && ($real_trigger == 'bundle')) {
      $input = $form_state->getUserInput();
      $bundle_name = $input['bundle'];
      $this->assigner->setCurrent($this->assigner->getBundle($bundle_name));
    }
    elseif ($trigger['#name'] == 'bundle') {
      $bundle_name = $form_state->getValue('bundle', '');
      $this->assigner->setCurrent($this->assigner->getBundle($bundle_name));
    }
    elseif ($trigger['#name'] == 'newfeature') {
      return $this->redirect('features.edit');
    }
    else {
      $this->assigner->loadBundle();
    }
    $current_bundle = $this->assigner->getBundle();
    $this->assigner->assignConfigPackages();

    $packages = $this->featuresManager->getPackages();
    $config_collection = $this->featuresManager->getConfigCollection();

    // Add in un-packaged configuration items.
    $this->addUnpackaged($packages, $config_collection);

    // Filter packages on bundle if selected.
    if (!$current_bundle->isDefault()) {
      $packages = $this->featuresManager->filterPackages($packages, $current_bundle->getMachineName(), TRUE);
    }

    // Pass the packages and bundle data for use in the form pre_render
    // callback.
    $form['#packages'] = $packages;
    $form['#profile_package'] = $current_bundle->getProfileName();
    $form['header'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => 'features-header'),
    );

    $form['#prefix'] = '<div id="edit-features-wrapper">';
    $form['#suffix'] = '</div>';
    $form['header']['bundle'] = array(
      '#title' => t('Bundle'),
      '#type' => 'select',
      '#options' => $this->assigner->getBundleOptions(),
      '#default_value' => $current_bundle->getMachineName(),
      '#prefix' => '<div id="edit-package-set-wrapper">',
      '#suffix' => '</div>',
      '#ajax' => array(
        'callback' => '::updatePreview',
        'wrapper' => 'edit-features-preview-wrapper',
      ),
      '#attributes' => array(
        'data-new-package-set' => 'status',
      ),
    );

    $form['header']['new'] = array(
      '#type' => 'button',
      '#name' => 'newfeature',
      '#value' => t('Create new feature'),
    );

    $form['preview'] = $this->buildListing($packages);

    $form['#attached'] = array(
      'library' => array(
        'features_ui/drupal.features_ui.admin',
      ),
    );

    if (\Drupal::currentUser()->hasPermission('export configuration')) {
      // Offer available generation methods.
      $generation_info = $this->generator->getGenerationMethods();
      // Sort generation methods by weight.
      uasort($generation_info, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

      $form['description'] = array(
        '#markup' => '<p>' . $this->t('Use an export method button below to generate the selected features.') . '</p>',
      );

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
    }

    $form['#pre_render'][] = array(get_class($this), 'preRenderRemoveInvalidCheckboxes');

    return $form;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function updatePreview($form, FormStateInterface $form_state) {
    // We should really be able to add this pre_render callback to the
    // 'preview' element. However, since doing so leads to an error (no rows
    // are displayed), we need to instead explicitly invoke it here for the
    // processing to apply to the Ajax-rendered form element.
    $form = $this->preRenderRemoveInvalidCheckboxes($form);
    return $form['preview'];
  }

  /**
   * Builds the portion of the form showing a listing of features.
   *
   * @param array $packages
   *   The packages.
   *
   * @return array
   *   A render array of a form element.
   */
  protected function buildListing(array $packages) {

    $header = array(
      'name' => array('data' => $this->t('Feature')),
      'machine_name' => array('data' => $this->t('')),
      'details' => array('data' => $this->t('Description'), 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      'version' => array('data' => $this->t('Version'), 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      'status' => array('data' => $this->t('Status'), 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      'state' => array('data' => $this->t('State'), 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    );

    $options = array();
    $first = TRUE;
    foreach ($packages as $package) {
      if ($first && $package['status'] == FeaturesManagerInterface::STATUS_NO_EXPORT) {
        // Don't offer new non-profile packages that are empty.
        if ($package['status'] === FeaturesManagerInterface::STATUS_NO_EXPORT &&
          !$this->assigner->getBundle()->isProfilePackage($package['machine_name']) &&
          empty($package['config'])) {
          continue;
        }
        $first = FALSE;
        $options[] = array(
          'name' => array(
            'data' => t('The following packages are not exported.'),
            'class' => 'features-export-header-row',
            'colspan' => 6,
          ),
        );
      }
      $options[$package['machine_name']] = $this->buildPackageDetail($package);
    }

    $element = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#attributes' => array('class' => array('features-listing')),
      '#prefix' => '<div id="edit-features-preview-wrapper">',
      '#suffix' => '</div>',
    );

    return $element;
  }

  /**
   * Builds the details of a package.
   *
   * @param array $package
   *   The package name.
   *
   * @return array
   *   A render array of a form element.
   */
  protected function buildPackageDetail(array $package) {
    $config_collection = $this->featuresManager->getConfigCollection();

    $url = Url::fromRoute('features.edit', array('featurename' => $package['machine_name']));

    $element['name'] = array(
      'data' => \Drupal::l($package['name'], $url),
      'class' => array('feature-name'),
    );
    $machine_name = $package['machine_name'];
    // Except for the 'unpackaged' pseudo-package, display the full name, since
    // that's what will be generated.
    if ($machine_name !== 'unpackaged') {
      $machine_name = $this->assigner->getBundle($package['bundle'])->getFullName($machine_name);
    }
    $element['machine_name'] = $machine_name;
    $element['status'] = array(
      'data' => $this->featuresManager->statusLabel($package['status']),
      'class' => array('column-nowrap'),
    );
    // Use 'data' instead of plain string value so a blank version doesn't
    // remove column from table.
    $element['version'] = array(
      'data' => SafeMarkup::checkPlain($package['version']),
      'class' => array('column-nowrap'),
    );
    $overrides = $this->featuresManager->detectOverrides($package);
    $new_config = $this->featuresManager->detectNew($package);
    $conflicts = array();
    $missing = array();

    if ($package['status'] == FeaturesManagerInterface::STATUS_NO_EXPORT) {
      $overrides = array();
      $new_config = array();
    }
    // Bundle package configuration by type.
    $package_config = array();
    foreach ($package['config'] as $item_name) {
      $item = $config_collection[$item_name];
      $package_config[$item->getType()][] = array(
        'name' => SafeMarkup::checkPlain($item_name),
        'label' => SafeMarkup::checkPlain($item->getLabel()),
        'class' => in_array($item_name, $overrides) ? 'features-override' :
          (in_array($item_name, $new_config) ? 'features-detected' : ''),
      );
    }
    // Conflict config from other modules.
    if (!empty($package['config_orig'])) {
      foreach ($package['config_orig'] as $item_name) {
        if (!isset($config_collection[$item_name])) {
          $missing[] = $item_name;
          $package_config['missing'][] = array(
            'name' => SafeMarkup::checkPlain($item_name),
            'label' => SafeMarkup::checkPlain($item_name),
            'class' => 'features-conflict',
          );
        }
        elseif (!in_array($item_name, $package['config'])) {
          $item = $config_collection[$item_name];
          $conflicts[] = $item_name;
          $package_config[$item->getType()][] = array(
            'name' => SafeMarkup::checkPlain($item_name),
            'label' => SafeMarkup::checkPlain($item->getLabel()),
            'class' => 'features-conflict',
          );
        }
      }
    }
    // Add dependencies.
    $package_config['dependencies'] = array();
    if (!empty($package['dependencies'])) {
      foreach ($package['dependencies'] as $dependency) {
        $package_config['dependencies'][] = array(
          'name' => $dependency,
          'label' => $this->moduleHandler->getName($dependency),
          'class' => '',
        );
      }
    }

    $class = '';
    $label = '';
    if (!empty($conflicts)) {
      $url = Url::fromRoute('features.edit', array('featurename' => $package['machine_name']));
      $class = 'features-conflict';
      $label = t('Conflicts');
    }
    elseif (!empty($overrides)) {
      $url = Url::fromRoute('features.diff', array('featurename' => $package['machine_name']));
      $class = 'features-override';
      $label = $this->featuresManager->stateLabel(FeaturesManagerInterface::STATE_OVERRIDDEN);
    }
    elseif (!empty($new_config)) {
      $url = Url::fromRoute('features.diff', array('featurename' => $package['machine_name']));
      $class = 'features-detected';
      $label = t('New detected');
    }
    elseif (!empty($missing)) {
      $url = Url::fromRoute('features.edit', array('featurename' => $package['machine_name']));
      $class = 'features-conflict';
      $label = t('Missing');
    }
    if (!empty($class)) {
      $element['state'] = array(
        'data' => \Drupal::l($label, $url),
        'class' => array($class, 'column-nowrap'),
      );
    }
    else {
      $element['state'] = '';
    }

    $config_types = $this->featuresManager->listConfigTypes();
    // Add dependencies.
    $config_types['dependencies'] = $this->t('Dependencies');
    $config_types['missing'] = $this->t('Missing');
    uasort($config_types, 'strnatcasecmp');

    $rows = array();
    // Use sorted array for order.
    foreach ($config_types as $type => $label) {
      // For each component type, offer alternating rows.
      $row = array();
      if (isset($package_config[$type])) {
        $row[] = array(
          'data' => array(
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => SafeMarkup::checkPlain($label),
            '#attributes' => array(
              'title' => SafeMarkup::checkPlain($type),
              'class' => 'features-item-label',
            ),
          ),
        );
        $row[] = array(
          'data' => array(
            '#theme' => 'features_items',
            '#items' => $package_config[$type],
            '#value' => SafeMarkup::checkPlain($label),
            '#title' => SafeMarkup::checkPlain($type),
          ),
          'class' => 'item',
        );
        $rows[] = $row;
      }
    }
    $element['table'] = array(
      '#type' => 'table',
      '#rows' => $rows,
    );

    $details = array();
    $details['description'] = array(
      '#markup' => Xss::filterAdmin($package['description']),
    );
    $details['table'] = array(
      '#type' => 'details',
      '#title' => array('#markup' => $this->t('Included configuration')),
      '#description' => array('data' => $element['table']),
    );
    $element['details'] = array(
      'class' => array('description', 'expand'),
      'data' => $details,
    );

    return $element;
  }

  /**
   * Adds a pseudo-package to display unpackaged configuration.
   *
   * @param array $packages
   *   An array of package names.
   * @param \Drupal\features\ConfigurationItem[] $config_collection
   *   A collection of configuration.
   */
  protected function addUnpackaged(array &$packages, array $config_collection) {
    $packages['unpackaged'] = array(
      'machine_name' => 'unpackaged',
      'name' => $this->t('Unpackaged'),
      'description' => $this->t('Configuration that has not been added to any package.'),
      'config' => array(),
      'status' => FeaturesManagerInterface::STATUS_NO_EXPORT,
      'version' => '',
    );
    foreach ($config_collection as $item_name => $item) {
      if (!$item->getPackage() && !$item->isExtensionProvided()) {
        $packages['unpackaged']['config'][] = $item_name;
      }
    }
  }

  /**
   * Denies access to the checkboxes for uninstalled or empty packages and the
   * "unpackaged" pseudo-package.
   *
   * @param array $form
   *   The form build array to alter.
   *
   * @return array
   *   The form build array.
   */
  public static function preRenderRemoveInvalidCheckboxes(array $form) {
    foreach ($form['#packages'] as $package) {
      // Remove checkboxes for packages that:
      // - exist and are disabled, or
      // - have no configuration assigned and are not the profile, or
      // - are the "unpackaged" pseudo-package.
      if ($package['status'] == FeaturesManagerInterface::STATUS_DISABLED ||
        (empty($package['config']) && !($package['machine_name'] == $form['#profile_package'])) ||
        $package['machine_name'] == 'unpackaged') {
        $form['preview'][$package['machine_name']]['#access'] = FALSE;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_bundle = $this->assigner->loadBundle();
    $this->assigner->assignConfigPackages();

    $package_names = array_filter($form_state->getValue('preview'));

    if (empty($package_names)) {
      drupal_set_message(t('Please select one or more packages to export.'), 'warning');
      return;
    }

    $method_id = NULL;
    $trigger = $form_state->getTriggeringElement();
    $op = $form_state->getValue('op');
    if (!empty($trigger) && empty($op)) {
      $method_id = $trigger['#name'];
    }

    if (!empty($method_id)) {
      $this->generator->generatePackages($method_id, $package_names, $current_bundle);
      $this->generator->applyExportFormSubmit($method_id, $form, $form_state);
    }
  }

}
