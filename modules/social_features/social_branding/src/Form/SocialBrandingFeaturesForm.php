<?php

namespace Drupal\social_branding\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialBrandingFeaturesForm.
 *
 * @package Drupal\social_branding\Form
 */
class SocialBrandingFeaturesForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new DataPolicyRevisionDeleteForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : SocialBrandingFeaturesForm {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_branding.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_branding_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Show a list of features.
    $form['features'] = [
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Weight'),
        $this->t('Feature'),
      ],
      '#attributes' => [
        'id' => 'branding-table',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'draggable-weight',
        ],
      ],
      '#caption' => $this->t('Keep in mind, only the first four features will be shown in the app.'),
    ];

    // Get all features created through existing hooks.
    $features = $this->getFeatures();

    foreach ($features as $feature) {
      // By default we use the Plugin weight.
      $weight = $feature->getWeight();

      $form['features'][$feature->getName()] = [
        'data' => [],
        '#attributes' => [
          'class' => [
            'draggable',
          ],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => t('Weight'),
          '#title_display' => '',
          '#default_value' => $weight,
          '#attributes' => [
            'class' => [
              'draggable-weight',
            ],
          ],
        ],
        'label' => [
          '#markup' => $feature->getName(),
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get all preferred features.
   *
   * @return array
   *   Array containing all preferred features.
   */
  private function getFeatures() : array {
    $preferred_features =& drupal_static(__METHOD__, []);

    if (empty($preferred_features)) {
      // Grab the features defined by the hook, this is used
      // as default for our form.
      $preferred_features = $this->moduleHandler->invokeAll('social_branding_preferred_features');

      // Grab the saved config, if there is config already saved.
      $config = $this->config('social_branding.settings');
      $features = $config->get('features');
      if (!empty($features)) {
        foreach ($features as $name => $weight) {
          $weight = $weight['weight'];
          // If we have saved the weight before, lets grab that instead.
          // this way the configuration is more important than the plugin,
          // but just for the weight.
          // We don't add / remove features based on existing config, as
          // the hook with $defined_features is leading, and new
          // PreferredFeatures can be added or old ones removed.
          $preferred_features = $this->updateDefinedFeature($name, $weight, $preferred_features);
        }
      }

      // Order our features ascending by weight.
      // So we render our form based on weight.
      usort($preferred_features, function ($item1, $item2) {
        return $item1->getWeight() <=> $item2->getWeight();
      });
    }

    return $preferred_features;
  }

  /**
   * Update the preferred feature with the updated weight.
   *
   * @param string $name
   *   The preferred feature name.
   * @param int $weight
   *   The new weight.
   * @param array $defined_features
   *   The initially defined features.
   *
   * @return array
   *   The preferred features.
   */
  private function updateDefinedFeature(string $name, int $weight, array $defined_features) : array {
    // Loop through the defined features.
    foreach ($defined_features as &$feature) {
      // If we have a matching one, update the weight.
      if ($feature->getName() === $name) {
        $feature->setWeight($weight);
      }
    }

    return $defined_features;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    parent::submitForm($form, $form_state);

    $this->config('social_branding.settings')
      ->set('features', $form_state->getValue('features'))
      ->save();
  }

}
