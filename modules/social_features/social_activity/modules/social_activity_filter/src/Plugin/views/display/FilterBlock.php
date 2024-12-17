<?php

namespace Drupal\social_activity_filter\Plugin\views\display;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\social_activity\Plugin\views\display\ModeBlock;

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
class FilterBlock extends ModeBlock {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
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
  public function blockSettings(array $settings): array {
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
  public function optionsSummary(mixed &$categories, mixed &$options): void {
    parent::optionsSummary($categories, $options);

    if ($this->getOption('override_tags_filter')) {
      $options['allow']['value'] .= ', ' . $this->t('Overridden Tags filter');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
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
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::submitOptionsForm($form, $form_state);

    if ($form_state->get('section') === 'allow') {
      $this->setOption('override_tags_filter', $form_state->getValue('override_tags_filter'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state): array {
    parent::blockForm($block, $form, $form_state);

    // Check if overridden filter option is enabled for current views block.
    if (!$this->getOption('override_tags_filter')) {
      return $form;
    }

    /** @var array $allow_settings */
    $allow_settings = $this->getOption('tags_filter');
    $allow_settings += array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();

    if (isset($block_configuration['delta'])) {
      $delta = $block_configuration['delta'];
    }
    else {
      /** @var \ArrayAccess $triggered */
      $triggered = $form_state->getTriggeringElement();
      $delta = is_int($triggered['#parents'][1]) ? $triggered['#parents'][1] : '';
    }

    if ($vid = $form_state->get('new_options_tags_' . $delta)) {
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
          $form['override']['vocabulary'] = [
            '#type' => 'select',
            '#title' => $this->t('Vocabulary'),
            '#options' => $this->getVocabularyOptionsList(),
            '#default_value' => $block_configuration['vocabulary'],
            '#empty_option' => $this->t('None'),
            '#empty_value' => '_none',
            '#ajax' => [
              'callback' => [static::class, 'updateTagsOptions'],
              'wrapper' => 'edit-block-term-wrapper-' . $delta,
            ],
          ];
          $form['override']['delta'] = [
            '#type' => 'hidden',
            '#value' => $delta,
          ];
          break;

        case 'tags':
          // Adds workaround to hide/display tags field due to "states" issue in
          // block_field plugin.
          $hidden = empty($opt) ? 'hidden' : '';

          $form['override']['tags'] = [
            '#type' => 'select',
            '#title' => $this->t('Tags'),
            '#description' => $this->t('Select the tags to filter items in the stream.'),
            '#default_value' => $block_configuration['tags'],
            '#options' => $opt,
            '#multiple' => TRUE,
            '#required' => !empty($opt) ? TRUE : FALSE,
            '#prefix' => '<div id="edit-block-term-wrapper-' . $delta . '" class="' . $hidden . '">',
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

  /**
   * Processes the tags form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processFilterTags(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    // Get selected vocabulary value.
    $parents = $element["#parents"];
    $input = $form_state->getUserInput();
    $values = NestedArray::getValue($input, $parents);

    // Save it & use to build new_options list tags.
    if (isset($values['vocabulary'])) {
      $form_state->set('new_options_tags_' . $values['delta'], $values['vocabulary']);
    }
    return $element;
  }

  /**
   * Handles switching the available terms based on the selected vocabulary.
   */
  public static function updateTagsOptions(array $form, FormStateInterface $form_state): ?AjaxResponse {

    // Check if there is triggered parent of element.
    if ($triggered = $form_state->getTriggeringElement()) {
      $delta = is_int($triggered['#parents'][1]) ? $triggered['#parents'][1] : '';

      // Build array of parents for triggered child element.
      $parents = $triggered['#array_parents'];
      array_pop($parents);
      array_push($parents, 'tags');

      // Get triggered child element.
      $element = NestedArray::getValue($form, $parents);

      // Return child element.
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#edit-block-term-wrapper-' . $delta, $element));

      return $response;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state): void {
    parent::blockSubmit($block, $form, $form_state);

    if ($tags = $form_state->getValue(['override', 'tags'])) {
      $block->setConfigurationValue('tags', $tags);
    }
    $form_state->unsetValue(['override', 'tags']);

    if ($vocabulary = $form_state->getValue(['override', 'vocabulary'])) {
      $block->setConfigurationValue('vocabulary', $vocabulary);
    }
    $form_state->unsetValue(['override', 'vocabulary']);

    // Always save delta of element.
    $delta = $form_state->getValue(['override', 'delta']);
    $block->setConfigurationValue('delta', $delta);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block): void {
    parent::preBlockBuild($block);

    // Prepare values to use it in the views filter.
    $block_configuration = $block->getConfiguration();

    if (isset($block_configuration['tags'])) {
      $this->view->filter['tags'] = $block_configuration['tags'];
    }

    $taxonomy_fields = $this->configFactory
      ->getEditable('social_activity_filter.settings')
      ->get('taxonomy_fields');
    $vid = $block_configuration['vocabulary'];

    if (!empty($taxonomy_fields[$vid])) {
      $this->view->filter['vocabulary'] = $taxonomy_fields[$vid];
    }
    else {
      if (isset($this->view->filter['vocabulary'])) {
        $vocabulary_filter = $this->view->filter['vocabulary'];
        $vocabulary_filter->value = '';
      }

    }
  }

  /**
   * Get vocabulary options list.
   *
   * @return array
   *   The vocabulary list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getVocabularyOptionsList(): array {
    $config = $this->configFactory->getEditable('social_activity_filter.settings');

    $allowed_list = $config->get('vocabulary');

    $vocabularies = $this->entityTypeManager
      ->getStorage('taxonomy_vocabulary')
      ->loadMultiple();

    $vocabulary_list = [];
    foreach ($vocabularies as $vid => $vocabulary) {

      if (!in_array($vid, $allowed_list)) {
        continue;
      }

      $vocabulary_list[$vid] = $vocabulary->get('name');
    }
    return $vocabulary_list;
  }

  /**
   * Get term options list.
   *
   * @param string $vid
   *   The vocabulary id.
   *
   * @return array
   *   The options term list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTermOptionslist(string $vid): array {
    $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $taxonomy_terms = $taxonomy_storage->loadTree($vid);
    $term_list = [];
    /** @var \Drupal\taxonomy\Entity\Term $taxonomy_term */
    foreach ($taxonomy_terms as $taxonomy_term) {
      $term_list[$taxonomy_term->get('tid')->getValue()] = $taxonomy_term->getName();
    }
    return $term_list;
  }

}
