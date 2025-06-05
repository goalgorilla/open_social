<?php

namespace Drupal\social\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal;
use Drupal\taxonomy\TermInterface;
use RuntimeException;

/**
 * Defines test steps around the usage of user.
 */
class TaggingContext extends RawMinkContext {

  /**
   * Add a tag to an existing topic.
   *
   * When moving to Drupal 10.4.x, we face the following exception when referring
   * to the 'social_tagging' vocabulary:
   * RuntimeException: The topic you tried to create is invalid: Object(Drupal\
   * Core\Entity\Plugin\DataType\EntityAdapter).social_tagging.0.target_id:
   * This entity (taxonomy_term: 7>) cannot be referenced.
   * This way we associate the tag with the topic after the topic is created.
   *
   * @Given I add the tag :tag_name to the topic :topic_title
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function addTagToTopic(string $tag_name, string $topic_title): void {
    // Find the topic by title.
    $nodes = Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'title' => $topic_title,
      'type' => 'topic',
    ]);

    if (empty($nodes)) {
      throw new RuntimeException("Topic '$topic_title' not found.");
    }

    $node = reset($nodes);

    // Find the term by name.
    $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => $tag_name,
      'vid' => 'social_tagging',
    ]);

    if (empty($terms)) {
      throw new RuntimeException("Tag '$tag_name' not found in social_tagging vocabulary.");
    }

    $term = reset($terms);

    // Add the tag to the node.
    if ($node->hasField('social_tagging')) {
      $current_tags = $node->get('social_tagging')->getValue();
      $current_tags[] = ['target_id' => $term->id()];
      $node->set('social_tagging', $current_tags);
      $node->save();
    } else {
      throw new RuntimeException("Topic does not have social_tagging field.");
    }
  }

  /**
   * Add a group type to an existing group.
   *
   * @Given I add the group type :group_type_name to the group :group_title
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function addGroupTypeToGroup(string $group_type_name, string $group_title): void {
    // Find the group by title.
    $groups = Drupal::entityTypeManager()->getStorage('group')->loadByProperties([
      'label' => $group_title,
    ]);

    if (empty($groups)) {
      throw new RuntimeException("Group '$group_title' not found.");
    }

    $group = reset($groups);

    // Find the group type term by name.
    $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => $group_type_name,
      'vid' => 'group_type',
    ]);

    if (empty($terms)) {
      throw new RuntimeException("Group type '$group_type_name' not found in group_type vocabulary.");
    }

    $term = reset($terms);

    // Add the group type to the group.
    if ($group->hasField('field_group_type')) {
      $group->set('field_group_type', ['target_id' => $term->id()]);
      $group->save();
    } else {
      throw new RuntimeException("Group does not have field_group_type field.");
    }
  }

  /**
   * Fill placement data to show tag for entities.
   *
   * @Given I enable content tag :term_name for all entities
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enableContentTagForAllEntities(string $term_name): void {
    $term = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term_name]);
    $term = reset($term);
    if (!$term instanceof TermInterface) {
      throw new RuntimeException("Term '$term_name' does not exist.");
    }
    /** @var \Drupal\social_tagging\SocialTaggingServiceInterface $helper */
    $helper = Drupal::service('social_tagging.tag_service');
    $options = $helper->getKeyValueOptions();
    // Option contains key=>value array where values are a label.
    // Get keys, and serialize like in TaggingUsageWidget.
    $values = array_keys($options);
    $term->set('field_category_usage', serialize($values))->save();
  }

}
