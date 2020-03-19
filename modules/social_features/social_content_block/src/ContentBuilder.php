<?php

namespace Drupal\social_content_block;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Class ContentBuilder.
 *
 * @package Drupal\social_content_block
 */
class ContentBuilder implements ContentBuilderInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The content block manager.
   *
   * @var \Drupal\social_content_block\ContentBlockManagerInterface
   */
  protected $contentBlockManager;

  /**
   * ContentBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current active database's master connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\social_content_block\ContentBlockManagerInterface $content_block_manager
   *   The content block manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $string_translation,
    ContentBlockManagerInterface $content_block_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($string_translation);
    $this->contentBlockManager = $content_block_manager;
  }

  /**
   * Function to get all the entities based on the filters.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content where we get the settings from.
   *
   * @return array
   *   Returns the entities found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntities(BlockContentInterface $block_content) {
    $plugin_id = $block_content->field_plugin_id->value;
    $definition = $this->contentBlockManager->getDefinition($plugin_id);

    // When the user didn't select any filter in the "Content selection" field
    // then the block base query will be built based on all filled filterable
    // fields.
    if ($block_content->field_plugin_field->isEmpty()) {
      $field_names = $definition['fields'];
    }
    // When the user selected some filter in the "Content selection" field then
    // only condition based on this filter field will be added to the block base
    // query.
    else {
      $field_names = [$block_content->field_plugin_field->value];
    }

    $fields = [];

    foreach ($field_names as $field_name) {
      $field = $block_content->get($field_name);

      if (!$field->isEmpty()) {
        $fields[$field_name] = array_map(function ($item) {
          return $item['target_id'];
        }, $field->getValue());
      }
    }

    /** @var \Drupal\social_content_block\ContentBlockPluginInterface $plugin */
    $plugin = $this->contentBlockManager->createInstance($plugin_id);

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition($definition['entityTypeId']);

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->connection->select($entity_type->getDataTable(), 'base_table')
      ->fields('base_table', [$entity_type->getKey('id')]);

    if ($definition['bundle']) {
      $query->condition(
        'base_table.' . $entity_type->getKey('bundle'),
        $definition['bundle']
      );
    }

    if ($fields) {
      $plugin->query($query, $fields);
    }

    // Allow other modules to change the query to add additions.
    $this->moduleHandler->alter('social_content_block_query', $query, $block_content);

    // Add sorting.
    $query->orderBy('base_table.' . $block_content->field_sorting->value);

    // Add range.
    $query->range(0, $block_content->field_item_amount->value);

    // Execute the query to get the results.
    $entities = $query->execute()->fetchAllKeyed(0, 0);

    if ($entities) {
      // Load all the topics so we can give them back.
      $entities = $this->entityTypeManager
        ->getStorage($definition['entityTypeId'])
        ->loadMultiple($entities);

      return $this->entityTypeManager
        ->getViewBuilder($definition['entityTypeId'])
        ->viewMultiple($entities, 'small_teaser');
    }

    return [
      '#markup' => '<div class="card__block">' . $this->t('No matching content found') . '</div>',
    ];
  }

  /**
   * Function to generate the read more link.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content where we get the settings from.
   *
   * @return array
   *   The read more link render array.
   */
  protected function getLink(BlockContentInterface $block_content) : array {
    $field = $block_content->field_link;

    if (!$field->isEmpty()) {
      $url = Url::fromUri($field->uri);
      $link_options = [
        'attributes' => [
          'class' => [
            'btn',
            'btn-flat',
          ],
        ],
      ];
      $url->setOptions($link_options);

      return Link::fromTextAndUrl($field->title, $url)->toRenderable();
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function build($entity_id, $entity_type_id, $entity_bundle) : array {
    if ($entity_type_id !== 'block_content' || $entity_bundle !== 'custom_content_list') {
      return [];
    }

    $block_content = $this->entityTypeManager->getStorage('block_content')
      ->load($entity_id);

    if (
      !$block_content instanceof BlockContentInterface ||
      $block_content->bundle() !== $entity_bundle
    ) {
      return [];
    }

    $build['content'] = [];

    $build['content']['entities'] = $this->getEntities($block_content);
    // If it's not an empty list, add a helper wrapper for theming.
    if (!isset($build['content']['entities']['#markup'])) {
      $build['content']['entities']['#prefix'] = '<div class="content-list__items">';
      $build['content']['entities']['#suffix'] = '</div>';
    }

    $link = $this->getLink($block_content);
    if (!empty($link)) {
      $build['content']['link'] = [
        '#type' => 'inline_template',
        '#template' => '<footer class="card__actionbar">{{link}}</footer>',
        '#context' => [
          'link' => $link,
        ],
      ];
    }

    return $build;
  }

  /**
   * Process callback to insert a Custom Block form.
   *
   * @param array $element
   *   The containing element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The containing element, with the Custom Block form inserted.
   */
  public static function processBlockForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\social_content_block\ContentBlockManagerInterface $content_block_manager */
    $content_block_manager = \Drupal::service('plugin.manager.content_block');

    $selector = $content_block_manager->getSelector('field_plugin_id', 'value');

    foreach ($content_block_manager->getDefinitions() as $plugin_id => $plugin_definition) {
      $fields = &$element['field_plugin_field']['widget'][0][$plugin_id]['#options'];

      foreach ($fields as $field_name => $field_title) {
        // When the filter field was absent during executing the code of the
        // field widget plugin for the filters list field then process this
        // field repeatedly.
        // @see \Drupal\social_content_block\Plugin\Field\FieldWidget\ContentBlockPluginFieldWidget::formElement()
        if ($field_name === $field_title) {
          $fields[$field_name] = $element[$field_name]['widget']['target_id']['#title'];

          $element[$field_name]['#states'] = [
            'visible' => [
              $selector => [
                'value' => $plugin_id,
              ],
              $content_block_manager->getSelector('field_plugin_field', $plugin_id) => [
                ['value' => 'all'],
                ['value' => $field_name],
              ],
            ],
          ];
        }
      }
    }

    return $element;
  }

}
