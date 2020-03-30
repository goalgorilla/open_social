<?php

namespace Drupal\social_activity_filter\Plugin\views\display;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "filter_block",
 *   title = @Translation("Filter Block"),
 *   help = @Translation("Display the view as a filter block."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Filter Block")
 * )
 *
 * @see \Drupal\views\Plugin\Block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class FilterBlock extends Block {

  use DeprecatedServicePropertyTrait;

  /**
   * Constructs a new Block instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, BlockManagerInterface $block_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $block_manager);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['tags_filter'] = [
      'contains' => [
        'vocabulary' => ['default' => 'vocabulary'],
        'tags' => ['default' => 'tags'],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);

    $settings['vocabulary'] = 'none';
    $settings['tags'] = 'none';

    return $settings;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    if ($this->getOption('override_tags_filter')) {
      $options['allow']['value'] .= ', ' . $this->t('Overridden Tags filter');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($form_state->get('section') !== 'allow') {
      return;
    }
    $customized_filters = $this->getOption('override_tags_filter');
    $form['override_tags_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override Tags filters'),
      '#description' => $this->t('Select the filters which users should be able to customize default values for when placing the views block into a layout.'),
      '#default_value' => !empty($customized_filters) ? $customized_filters : [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    if ($form_state->get('section') === 'allow') {
      $this->setOption('override_tags_filter', $form_state->getValue('override_tags_filter'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    parent::blockForm($block, $form, $form_state);

    // Check if overridden filter option is enabled for current views block.
    if (!$this->getOption('override_tags_filter')) {
      return $form;
    }

    $allow_settings = $this->getOption('tags_filter');
    $allow_settings += array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();

    if ($vid = $form_state->get('new_options_tags')) {
      $opt = $this->getTermOptionslist($vid);
    }
    else {
      $opt = $this->getTermOptionslist($block_configuration['vocabulary']);
    }

    foreach ($allow_settings as $type => $enabled) {
      if (empty($enabled)) {
        continue;
      }
      switch ($type) {
        case 'vocabulary':
          $config = \Drupal::configFactory()
            ->getEditable('social_activity_filter.settings');

          $form['override']['vocabulary'] = [
            '#type' => 'select',
            '#title' => $this->t('Vocabulary'),
            '#options' => !empty($config->get('vocabulary')) ? array_filter($config->get('vocabulary')) : [],
            '#default_value' => $block_configuration['vocabulary'],
            '#empty_option' => t('None'),
            '#required' => TRUE,
            '#ajax' => [
              'callback' => [static::class, 'updateTagsOptions'],
              'wrapper' => 'edit-block-term-wrapper',
            ],
          ];
          break;
        case 'tags':
          $form['override']['tags'] = [
            '#type' => 'select',
            '#title' => $this->t('Tags'),
            '#description' => $this->t('Select the tags to filter items in the stream.'),
            '#default_value' => $block_configuration['tags'],
            '#options' => $opt,
            '#multiple' => TRUE,
            '#required' => TRUE,
            '#prefix' => '<div id="edit-block-term-wrapper">',
            '#suffix' => '</div>',
          ];
          break;
        case 'items_per_page':
          $form['override']['items_per_page']['#weight'] = 10;
          break;
      }
    }

    $form['override']['#process'] = [
      [static::class, 'processFilterTags'],
    ];

    return $form;
  }

  public static function processFilterTags(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $input = $form_state->getUserInput();

    if (isset($input['settings']['override']['vocabulary'])) {
      $override = $input['settings']['override']['vocabulary'];
      $form_state->set('new_options_tags', $override);
    }

    return $element;
  }

  /**
   * Handles switching the available terms based on the selected vocabulary.
   */
  public static function updateTagsOptions($form, FormStateInterface $form_state) {
    return $form['settings']['override']['tags'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);

    if ($items_per_page = $form_state->getValue(['override', 'tags'])) {
      $block->setConfigurationValue('tags', $items_per_page);
    }
    $form_state->unsetValue(['override', 'tags']);

    if ($items_per_page = $form_state->getValue(['override', 'vocabulary'])) {
      $block->setConfigurationValue('vocabulary', $items_per_page);
    }
    $form_state->unsetValue(['override', 'vocabulary']);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);

    $config = $block->getConfiguration();

    $this->view->filter_tags = $config['tags'];

    //@todo: Needs imporve it!
    if (strpos($config['vocabulary'], 'cop') !== NULL) {
      $this->view->filter_vocabulary = 'cop_tags';
    }
    else {
      $this->view->filter_vocabulary = $config['vocabulary'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTermOptionslist($vid) {
    $taxonomy_storage = \Drupal::service('entity_type.manager')
      ->getStorage("taxonomy_term");
    $taxonomy_terms = $taxonomy_storage->loadTree($vid);
    $term_list = [];
    /** @var \Drupal\taxonomy\Entity\Term $taxonomy_term */
    foreach ($taxonomy_terms as $taxonomy_term) {
      $term_list[$taxonomy_term->tid] = $taxonomy_term->name;
    }
    return $term_list;
  }

}
