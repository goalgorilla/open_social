<?php

/**
 * @file
 * Post update hooks for the Social Course module.
 *
 * These hooks run when the database matches the code so it's safe for them to
 * manipulate data using the Entity or Field APIs.
 */

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\social_course\Entity\CourseEnrollment;
use Drupal\user\Entity\User;

/**
 * Toggle search indexes to fix fields in index.
 */
function social_course_post_update_0001_fix_search_indexes(): void {
  // The search indexes are disabled and re-enabled to ensure that the added/
  // changed fields are properly added to the database.
  try {
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage("search_api_index");
  }
  // If the search_api is not available then we're done.
  catch (PluginNotFoundException $e) {
    return;
  }

  // Load all indexes that are somehow linked to groups
  // (including group content).
  $indices = $storage->loadMultiple([
    'search_api.index.social_all',
    'search_api.index.social_content',
    'search_api.index.social_groups',
  ]);

  /** @var \Drupal\search_api\Entity\Index $index */
  foreach ($indices as $index) {
    // If an index is already unused then we can skip it.
    if (!$index->status()) {
      continue;
    }

    $index->disable()->save();
    $index->enable()->save();
  }
}

/**
 * Clean up enrollments for non-existent parts or sections.
 */
function social_course_post_update_0002_clean_orphaned_enrollments(array &$sandbox): void {
  // These enrollments could've been made when a user went through a course
  // and there was still a reference to a non-existing node.
  // If this is the first run, set-up the batch operations.
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    // Use array_values to get the entity_ids. No need to know about revisions.
    $sandbox['ceids'] = array_values(
      \Drupal::entityQuery('course_enrollment')
        // Run query as administrator to avoid access checks.
        ->addMetaData('account', User::load(1))
        ->execute()
    );
    $sandbox['enrollment_count'] = count($sandbox['ceids']);
  }

  // Ensure the platform can determine how many items we process.
  $batch_size = Settings::get('entity_update_batch_size', 25);

  // Try to do 5 each cycle. Never do more than are available.
  for ($target = $sandbox['progress'] + $batch_size; $sandbox['progress'] < $target && $sandbox['progress'] < $sandbox['enrollment_count']; $sandbox['progress']++) {
    $enrollment = CourseEnrollment::load($sandbox['ceids'][$sandbox['progress']]);

    // If this enrollment has disappeared then we ignore it.
    if (!$enrollment) {
      continue;
    }

    $section_id = $enrollment->get('sid')->getValue();
    $material_id = $enrollment->get('mid')->getValue();

    // If the material or section for this enrollment no longer exists then we
    // remove the enrollment.
    $target_nodes = array_column(array_merge($section_id, $material_id), 'target_id');
    foreach ($target_nodes as $nid) {
      if (!Node::load($nid)) {
        $enrollment->delete();
      }
    }
  }

  $sandbox['#finished'] = empty($sandbox['enrollment_count']) ? 1 : ($sandbox['progress'] / $sandbox['enrollment_count']);
}

/**
 * Clean up section references to deleted materials.
 */
function social_course_post_update_0003_clean_up_material_references(array &$sandbox): void {
  // These references could still exist when a material was deleted and the part
  // wasn't saved afterwards before the hook_delete was implemented.
  // If this is the first run, set-up the batch operations.
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    // Use array_values to get the entity_ids. No need to know about revisions.
    $sandbox['csids'] = array_values(
      \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'course_section')
        // Run query as administrator to avoid node access checks.
        ->addMetaData('account', User::load(1))
        ->execute()
    );
    $sandbox['section_count'] = count($sandbox['csids']);
  }

  // Ensure the platform can determine how many items we process.
  $batch_size = Settings::get('entity_update_batch_size', 25);

  // Try to do 5 each cycle. Never do more than are available.
  for ($target = $sandbox['progress'] + $batch_size; $sandbox['progress'] < $target && $sandbox['progress'] < $sandbox['section_count']; $sandbox['progress']++) {
    $section = Node::load($sandbox['csids'][$sandbox['progress']]);

    // If this section has disappeared then we ignore it.
    if (!$section) {
      continue;
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $section_content */
    $section_content = $section->get('field_course_section_content');

    // Utilise the fact that `referencedEntities` omits non-existent entities.
    $existing_materials = $section_content->referencedEntities();
    $section_content_value = array_map(
      function ($material) {
        \Drupal::logger('social_course')->info("Material is @id", ["@id" => $material->id()]);
        return ['target_id' => $material->id()];
      },
      $existing_materials
    );

    $section_content->setValue($section_content_value);
    $section->save();
  }

  $sandbox['#finished'] = empty($sandbox['section_count']) ? 1 : ($sandbox['progress'] / $sandbox['section_count']);
}

/**
 * Clean up enrollments that exist for the anonymous user.
 */
function social_course_post_update_0004_remove_anonymous_enrollments(array &$sandbox): void {
  // The material pages didn't properly check access for anonymous users which
  // can cause enrollments to exist for the anonymous user.
  // If this is the first run, set-up the batch operations.
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    // Use array_values to get the entity_ids. No need to know about revisions.
    $sandbox['ceids'] = array_values(
      \Drupal::entityQuery('course_enrollment')
        ->condition('uid', 0)
        ->execute()
    );
    $sandbox['enrollment_count'] = count($sandbox['ceids']);
  }

  // Ensure the platform can determine how many items we process.
  $batch_size = Settings::get('entity_update_batch_size', 50);

  // Try to do 5 each cycle. Never do more than are available.
  for ($target = $sandbox['progress'] + $batch_size; $sandbox['progress'] < $target && $sandbox['progress'] < $sandbox['enrollment_count']; $sandbox['progress']++) {
    $enrollment = CourseEnrollment::load($sandbox['ceids'][$sandbox['progress']]);

    // If this enrollment has disappeared then we ignore it.
    if (!$enrollment) {
      continue;
    }

    // Delete the enrollment for this user.
    $enrollment->delete();
  }

  $sandbox['#finished'] = empty($sandbox['enrollment_count']) ? 1 : ($sandbox['progress'] / $sandbox['enrollment_count']);
}

/**
 * Updates the node type visibility condition.
 */
function social_course_post_update_0005_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block_list = [
    'block.block.course_material_pager',
    'block.block.course_navigation',
    'block.block.socialblue_course_material_pager',
    'block.block.socialblue_course_navigation',
  ];

  foreach ($block_list as $block_config_name) {
    $block = $config_factory->getEditable($block_config_name);

    if ($block->get('visibility.node_type')) {
      $configuration = $block->get('visibility.node_type');
      $configuration['id'] = 'entity_bundle:node';
      $block->set('visibility.entity_bundle:node', $configuration);
      $block->clear('visibility.node_type');
      $block->save(TRUE);
    }
  }
}
