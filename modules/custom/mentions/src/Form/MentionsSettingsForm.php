<?php

namespace Drupal\mentions\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure mentions settings.
 */
class MentionsSettingsForm extends ConfigFormBase {


  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mentions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mentions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mentions.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General configuration'),
      '#open' => TRUE,
    ];

    $form['general']['supported_entity_types'] = [
      '#title' => $this->t('Supported entity types'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->getContentEntityTypes(),
      '#default_value' => $config->get('supported_entity_types'),
      '#description' => $this->t('Mentions entity will be created only for selected entity types.'),
    ];

    $form['general']['suggestions_amount'] = [
      '#title' => $this->t('Number of suggestions'),
      '#type' => 'number',
      '#default_value' => $config->get('suggestions_amount'),
      '#description' => $this->t('How many suggestions do you want to show when mentioning.'),
      '#min' => 0,
      '#max' => 100,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save config.
    $config = $this->config('mentions.settings');

    $config->set('supported_entity_types', $form_state->getValue('supported_entity_types'));

    $config->set('suggestions_amount', $form_state->getValue('suggestions_amount'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get content entity types keyed by id.
   *
   * @return array
   *   Returns array of content entity types.
   */
  protected function getContentEntityTypes() {
    $options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $options[$entity_type->id()] = $entity_type->getLabel();
      }
    }
    return $options;
  }

}
