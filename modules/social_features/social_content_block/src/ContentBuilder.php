<?php

namespace Drupal\social_content_block;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ContentBuilder.
 *
 * @package Drupal\social_content_block
 */
class ContentBuilder implements ContentBuilderInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Function to get all the topics based on the filters.
   *
   * @param \Drupal\block_content\Entity\BlockContent $blockContent
   *   The block content where we get the settings from.
   *
   * @return array|string
   *   Return the topics found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTopics(BlockContent $blockContent) {
    // Get topic type tags.
    $topic_types_list = $blockContent->get('field_topic_type')->getValue();
    $topic_types = array_map(function ($topic_type) {
      return $topic_type['target_id'];
    }, $topic_types_list);

    // Get group tags.
    $group_tag_list = $blockContent->get('field_group')->getValue();
    $group_tags = array_map(function ($group_tag) {
      return $group_tag['target_id'];
    }, $group_tag_list);

    // Get social tags.
    $social_tag_list = $blockContent->get('field_content_tags')->getValue();
    $social_tags = array_map(function ($social_tag) {
      return $social_tag['target_id'];
    }, $social_tag_list);

    $range = $blockContent->getFieldValue('field_item_amount', 'value');
    $sorting = $blockContent->getFieldValue('field_sorting', 'value');

    // Use database select because we need joins
    // which are not possible with entityQuery.
    $query = \Drupal::database()->select('node', 'n')
      ->fields('n', ['nid']);

    // Add field data.
    $query->condition('n.type', 'topic');
    $query->innerJoin('node_field_data', 'd', 'd.nid = n.nid');

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
    \Drupal::moduleHandler()->alter('social_content_block_query', $query);

    // Add sorting.
    $query->orderBy($sorting);

    // Add range.
    $query->range(0, $range);

    // Execute the query to get the results.
    $entities = $query->execute()->fetchAllKeyed(0, 0);

    if ($entities !== NULL) {
      // Load all the topics so we can give them back.
      $entities = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($entities);

      $topics = [];
      foreach ($entities as $entity) {
        /* @var \Drupal\Core\Entity\EntityViewBuilderInterface $viewBuilder */
        $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
        $topics[] = $viewBuilder->view($entity, 'small_teaser');
      }

      return $topics;
    }

    return '';
  }

  /**
   * Function to generate the read more link.
   *
   * @param \Drupal\block_content\Entity\BlockContent $blockContent
   *   The block content where we get the settings from.
   *
   * @return \Drupal\Core\GeneratedLink|string
   *   The read more link.
   */
  protected function getLink(BlockContent $blockContent) {
    $uri = $blockContent->get('field_link')->getValue();

    if (!empty($uri)) {
      $url = Url::fromUri($uri[0]['uri']);
      $link_options = [
        'attributes' => [
          'class' => [
            'btn',
            'btn-flat',
          ],
        ],
      ];
      $url->setOptions($link_options);

      if ($url instanceof Url) {
        return Link::fromTextAndUrl($uri[0]['title'], $url)->toString();
      }
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

    $block_content = BlockContent::load($entity_id);

    if (!$block_content instanceof BlockContent && $block_content->bundle() !== 'custom_content_list') {
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
