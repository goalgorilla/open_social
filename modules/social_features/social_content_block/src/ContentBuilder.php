<?php

namespace Drupal\social_content_block;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\social_content_block\ContentBlockManagerInterface $content_block_manager
   *   The content block manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $string_translation,
    ContentBlockManagerInterface $content_block_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
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

    if ($block_content->field_plugin_field->isEmpty()) {
      $field_names = $definition['fields'];
    }
    else {
      $field_names = [$block_content->field_plugin_field->value];
    }

    $fields = [];

    foreach ($field_names as $field_name) {
      $fields[$field_name] = array_map(function ($item) {
        return $item['target_id'];
      }, $block_content->get($field_name)->getValue());
    }

    /** @var \Drupal\social_content_block\ContentBlockPluginInterface $plugin */
    $plugin = $this->contentBlockManager->createInstance($plugin_id);

    $query = $plugin->query($fields);

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
   * @return string
   *   The read more link.
   */
  protected function getLink(BlockContentInterface $block_content) {
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

      return Link::fromTextAndUrl($field->title, $url)->toString();
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function build($entity_type_id, $entity_id) {
    if ($entity_type_id !== 'block_content') {
      return [];
    }

    $block_content = $this->entityTypeManager->getStorage('block_content')
      ->load($entity_id);

    if (
      !$block_content instanceof BlockContentInterface ||
      $block_content->bundle() !== 'custom_content_list'
    ) {
      return [];
    }

    $data = [
      '#theme' => 'social_content_block',
      '#title' => $block_content->label(),
      '#subtitle' => $block_content->field_subtitle->value,
      '#topics' => $this->getEntities($block_content),
      '#link' => $this->getLink($block_content),
    ];

    $build['content'] = $data;

    return $build;
  }

}
