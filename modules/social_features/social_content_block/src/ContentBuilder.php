<?php

namespace Drupal\social_content_block;

use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Database\Connection;
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
   * The module handler service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * ContentBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database Service Object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Function to get all the topics based on the filters.
   *
   * @param \Drupal\block_content\Entity\BlockContent $block_content
   *   The block content where we get the settings from.
   *
   * @return array|string
   *   Return the topics found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTopics(BlockContent $block_content) {
    // Get topic type tags.
    $topic_types_list = $block_content->get('field_topic_type')->getValue();
    $topic_types = array_map(function ($topic_type) {
      return $topic_type['target_id'];
    }, $topic_types_list);

    // Get group tags.
    $group_tag_list = $block_content->get('field_group')->getValue();
    $group_tags = array_map(function ($group_tag) {
      return $group_tag['target_id'];
    }, $group_tag_list);

    // Get social tags.
    $social_tag_list = $block_content->get('field_content_tags')->getValue();
    $social_tags = array_map(function ($social_tag) {
      return $social_tag['target_id'];
    }, $social_tag_list);

    // Use database select because we need joins
    // which are not possible with entityQuery.
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->connection->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'topic');

    // Add topic type tags.
    if (!empty($topic_types)) {
      $query->innerJoin('node__field_topic_type', 'tt', 'tt.entity_id = n.nid');
      $query->condition('tt.field_topic_type_target_id', $topic_types, 'IN');
    }

    // Add group tags.
    if (!empty($group_tags)) {
      $query->innerJoin('group_content_field_data', 'gd', 'gd.entity_id = n.nid');
      $query->condition('gd.gid', $group_tags, 'IN');
    }

    if (!empty($social_tags)) {
      $query->innerJoin('node__social_tagging', 'st', 'st.entity_id = n.nid');
      $query->condition('st.social_tagging_target_id', $social_tags, 'IN');
    }

    // Allow other modules to change the query to add additions.
    $this->moduleHandler->alter('social_content_block_query', $query, $block_content);

    // Add sorting.
    $query->orderBy('n.' . $block_content->getFieldValue('field_sorting', 'value'));

    // Add range.
    $query->range(0, $block_content->getFieldValue('field_item_amount', 'value'));

    // Execute the query to get the results.
    $entities = $query->execute()->fetchAllKeyed(0, 0);

    if ($entities) {
      // Load all the topics so we can give them back.
      $entities = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($entities);

      return $this->entityTypeManager->getViewBuilder('node')
        ->viewMultiple($entities, 'small_teaser');
    }

    return [
      '#markup' => '<div class="card__block">' . $this->t('No matching content found') . '</div>',
    ];
  }

  /**
   * Function to generate the read more link.
   *
   * @param \Drupal\block_content\Entity\BlockContent $block_content
   *   The block content where we get the settings from.
   *
   * @return string
   *   The read more link.
   */
  protected function getLink(BlockContent $block_content) {
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

    if (!$block_content instanceof BlockContentInterface || $block_content->bundle() !== 'custom_content_list') {
      return [];
    }

    $data = [
      '#theme' => 'social_content_block',
      '#title' => $block_content->label(),
      '#subtitle' => $block_content->getFieldValue('field_subtitle', 'value'),
      '#topics' => $this->getTopics($block_content),
      '#link' => $this->getLink($block_content),
    ];

    $build['content'] = $data;

    return $build;
  }

}
