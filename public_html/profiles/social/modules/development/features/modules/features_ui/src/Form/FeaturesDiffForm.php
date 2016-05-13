<?php

/**
 * @file
 * Contains \Drupal\features_ui\Form\FeaturesDiffForm.
 */

namespace Drupal\features_ui\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\features\ConfigurationItem;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\features\Package;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Diff\DiffFormatter;
use Drupal\config_update\ConfigRevertInterface;
use Drupal\config_update\ConfigDiffInterface;

/**
 * Defines the features differences form.
 */
class FeaturesDiffForm extends FormBase {

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
   * The config differ.
   *
   * @var \Drupal\config_update\ConfigDiffInterface
   */
  protected $configDiff;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The config reverter.
   *
   * @var \Drupal\config_update\ConfigRevertInterface
   */
  protected $configRevert;

  /**
   * Constructs a FeaturesDiffForm object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager.
   */
  public function __construct(FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner,
                              ConfigDiffInterface $config_diff, DiffFormatter $diff_formatter,
                              ConfigRevertInterface $config_revert) {
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
    $this->configDiff = $config_diff;
    $this->diffFormatter = $diff_formatter;
    $this->configRevert = $config_revert;
    $this->diffFormatter->show_header = FALSE;
    $this->diffFormatter->leading_context_lines = 0;
    $this->diffFormatter->trailing_context_lines = 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('features.manager'),
      $container->get('features_assigner'),
      $container->get('config_update.config_diff'),
      $container->get('diff.formatter'),
      $container->get('features.config_update')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'features_diff_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $featurename = '') {
    $current_bundle = $this->assigner->applyBundle();
    $packages = $this->featuresManager->getPackages();
    $form = array();

    $machine_name = '';
    if (!empty($featurename) && empty($packages[$featurename])) {
      drupal_set_message($this->t('Feature @name does not exist.', array('@name' => $featurename)), 'error');
      return array();
    }
    elseif (!empty($featurename)) {
      $machine_name = $packages[$featurename]->getMachineName();
      $packages = array($packages[$featurename]);
    }
    else {
      $packages = $this->featuresManager->filterPackages($packages, $current_bundle->getMachineName());
    }

    $header = array(
      'row' => array(
        'data' => !empty($machine_name)
        ? $this->t('Differences in @name', array('@name' => $machine_name))
        : ($current_bundle->isDefault() ? $this->t('All differences') : $this->t('All differences in bundle: @bundle', array('@bundle' => $current_bundle->getName()))),
      ),
    );

    $options = array();
    foreach ($packages as $package) {
      if ($package->getStatus() != FeaturesManagerInterface::STATUS_NO_EXPORT) {
        $missing = $this->featuresManager->reorderMissing($this->featuresManager->detectMissing($package));
        $overrides = $this->featuresManager->detectOverrides($package, TRUE);
        if (!empty($overrides) || !empty($missing)) {
          $options += array(
            $package->getMachineName() => array(
              'row' => array(
                'data' => array(
                  '#type' => 'html_tag',
                  '#tag' => 'h2',
                  '#value' => SafeMarkup::checkPlain($package->getName()),
                ),
              ),
              '#attributes' => array(
                'class' => 'features-diff-header',
              ),
            ),
          );
          $options += $this->diffOutput($package, $overrides, $missing);
        }
      }
    }

    $form['diff'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#attributes' => array('class' => array('features-diff-listing')),
      '#empty' => $this->t('No differences exist in exported features.'),
    );

    $form['actions'] = array('#type' => 'actions', '#tree' => TRUE);
    $form['actions']['revert'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import changes'),
    );
    $form['actions']['help'] = array(
      '#markup' => $this->t('Import the selected changes above into the active configuration.'),
    );

    $form['#attached']['library'][] = 'system/diff';
    $form['#attached']['library'][] = 'features_ui/drupal.features_ui.admin';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->assigner->assignConfigPackages();
    $config = $this->featuresManager->getConfigCollection();
    $items = array_filter($form_state->getValue('diff'));
    if (empty($items)) {
      drupal_set_message($this->t('No configuration was selected for import.'), 'warning');
      return;
    }
    foreach ($items as $config_name) {
      if (isset($config[$config_name])) {
        $item = $config[$config_name];
        $type = ConfigurationItem::fromConfigStringToConfigType($item->getType());
        $this->configRevert->revert($type, $item->getShortName());
      }
      else {
        $item = $this->featuresManager->getConfigType($config_name);
        $type = ConfigurationItem::fromConfigStringToConfigType($item['type']);
        $this->configRevert->import($type, $item['name_short']);
      }
      drupal_set_message($this->t('Imported @name', array('@name' => $config_name)));
    }
  }

  /**
   * Returns a form element for the given overrides.
   *
   * @param \Drupal\features\Package $package
   *   A package.
   * @param array $overrides
   *   An array of overrides.
   * @param array $missing
   *   An array of missing config.
   *
   * @return array
   *   A form element.
   */
  protected function diffOutput(Package $package, $overrides, $missing = array()) {
    $element = array();
    $config = $this->featuresManager->getConfigCollection();
    $components = array_merge($missing, $overrides);

    $header = array(
      array('data' => '', 'class' => 'diff-marker'),
      array('data' => $this->t('Active site config'), 'class' => 'diff-context'),
      array('data' => '', 'class' => 'diff-marker'),
      array('data' => $this->t('Feature code config'), 'class' => 'diff-context'),
    );

    foreach ($components as $name) {
      $rows[] = array(array('data' => $name, 'colspan' => 4, 'header' => TRUE));

      if (!isset($config[$name])) {
        $details = array(
          '#markup' => $this->t('Component in feature missing from active config.'),
        );
      }
      else {
        $active = $this->featuresManager->getActiveStorage()->read($name);
        $extension = $this->featuresManager->getExtensionStorages()->read($name);
        if (empty($extension)) {
          $details = array(
            '#markup' => $this->t('Dependency detected in active config but not exported to the feature.'),
          );
        }
        else {
          $diff = $this->configDiff->diff($active, $extension);
          $details = array(
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $this->diffFormatter->format($diff),
            '#attributes' => array('class' => array('diff', 'features-diff')),
          );
        }
      }
      $element[$name] = array(
        'row' => array(
          'data' => array(
            '#type' => 'details',
            '#title' => SafeMarkup::checkPlain($name),
            '#open' => TRUE,
            '#description' => array(
              'data' => $details,
            ),
          ),
        ),
        '#attributes' => array(
          'class' => 'diff-' . $package->getMachineName(),
        ),
      );
    }

    return $element;
  }

}
