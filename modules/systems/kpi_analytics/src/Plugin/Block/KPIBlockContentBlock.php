<?php

namespace Drupal\kpi_analytics\Plugin\Block;

use Drupal\block_content\Plugin\Block\BlockContentBlock;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide overrides for BlockContentBlock class.
 *
 * @package Drupal\kpi_analytics\Plugin\Block
 */
class KPIBlockContentBlock extends BlockContentBlock {

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Get saved terms.
    $default_terms = $this->configuration['taxonomy_filter'] ?? NULL;

    // Vocabularies for tag filter.
    $vids = [
      'social_tagging',
    ];

    $this->moduleHandler->alter('kpi_analytics_term_vocabularies', $vids);

    // Get all available vocabularies.
    $vocabularies = $this->entityTypeManager
      ->getStorage('taxonomy_vocabulary')
      ->loadByProperties([
        'vid' => $vids,
      ]);

    // Generate vocabulary options.
    $vocabulary_options = array_map(static fn($value): ?string => $value->label(), $vocabularies);

    // Get default vocabulary.
    $default_vocabulary = array_keys($vocabulary_options)[0];
    if (isset($this->configuration['vocabulary_filter'])) {
      $default_vocabulary = $this->configuration['vocabulary_filter'];
    }
    $user_input = $form_state->getUserInput();
    if (isset($user_input['settings']['taxonomy_filter']['vocabulary'])) {
      $default_vocabulary = $user_input['settings']['taxonomy_filter']['vocabulary'];
      $default_terms = NULL;
    }

    // Get terms options.
    $term_options = $this->getTermsByVocabulary($default_vocabulary);

    // Generate field for terms filtering.
    $form['taxonomy_filter'] = [
      '#type' => 'container',
      'vocabulary' => [
        '#type' => 'select',
        '#title' => t('Vocabulary'),
        '#options' => $vocabulary_options,
        '#default_value' => $default_vocabulary,
        '#value' => $default_vocabulary,
        '#ajax' => [
          'event' => 'change',
          'callback' => [$this, 'updateTermList'],
          'wrapper' => 'kpi-term-wrapper',
        ],
        '#required' => TRUE,
      ],
      'terms' => [
        '#type' => 'select2',
        '#title' => t('Terms'),
        '#options' => $term_options,
        '#default_value' => $default_terms,
        '#value' => $default_terms,
        '#multiple' => TRUE,
        '#required' => TRUE,
        '#prefix' => '<div id="kpi-term-wrapper">',
        '#suffix' => '</div>',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $user_inputs = $form_state->getUserInput();
    $this->setConfigurationValue('vocabulary_filter', $form_state->getValue([
      'taxonomy_filter',
      'vocabulary',
    ]));
    $this->setConfigurationValue('taxonomy_filter', $user_inputs['settings']['taxonomy_filter']['terms']);

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Ajax callback to return list of terms.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   *
   * @return array
   *   Render array of field.
   */
  public function updateTermList(array &$form): array {
    return $form['settings']['taxonomy_filter']['terms'];
  }

  /**
   * Returns list of terms.
   *
   * @param string $vocabulary
   *   Vocabulary id.
   *
   * @return array|string[]
   *   List of terms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTermsByVocabulary(string $vocabulary): array {
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => $vocabulary,
        'status' => 1,
      ]);
    if (!empty($terms)) {
      return array_map(static fn($value): ?string => $value->label(), $terms);
    }

    return [];
  }

}
