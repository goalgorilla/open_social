<?php

namespace Drupal\social_group_flexible_group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\Group;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_post\Entity\Post;
use Drupal\node\Entity\Node;
use Drupal\user\RoleInterface;

/**
 * Class FlexibleGroupContentVisibilityUpdate.
 *
 * @package Drupal\social_group_flexible_group
 */
class FlexibleGroupContentVisibilityUpdate {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Update Group content after Group changed.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group we've updated.
   * @param array $changed_visibility
   *   The Group's old visibility.
   * @param array $new_options
   *   The Group's new visibility options.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchUpdateGroupContentVisibility(GroupInterface $group, array $changed_visibility, array $new_options) {
    // Set it up as a batch. We need to update visibility.
    // Load all the GroupContentEntities from Post to content.
    // Memberships don't need an update.
    $entities = $posts = [];
    $entities = $group->getContentEntities();
    $posts = self::getPostsFromGroup($group);

    // Add posts to the entities we need to update based on visibility.
    if (!empty($posts)) {
      foreach ($posts as $pid => $post) {
        if (in_array($post->getVisibility(), $changed_visibility, FALSE)) {
          $entities[] = $post;
        }
      }
    }

    $num_operations = count($entities);
    $operations = [];

    // Update ContentVisibility.
    // As per documentation each entity has it's own operation.
    for ($i = 0; $i < $num_operations; $i++) {
      $operations[] = [
        '\Drupal\social_group_flexible_group\FlexibleGroupContentVisibilityUpdate::updateVisibility',
        [
          $entities[$i],
          $new_options,
        ],
      ];
    }

    // Provide all the operations and the finish callback to our batch.
    $batch = [
      'title' => t('Updating Group Content...'),
      'operations' => $operations,
      'finished' => '\Drupal\social_group_flexible_group\FlexibleGroupContentVisibilityUpdate::updateVisibilityFinishedCallback',
    ];

    batch_set($batch);
  }

  /**
   * Update visibility for all Group Content based on a new group type.
   *
   * @param \Drupal\node\Entity\Node|\Drupal\social_post\Entity\Post|\Drupal\group\GroupMembership|\Drupal\group\Entity\GroupContentInterface $entity
   *   The content we are updating.
   * @param array $new_options
   *   The Group's new visibility options.
   * @param array $context
   *   Passed on by reference.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateVisibility($entity, array $new_options, array &$context) {
    // Store some results for post-processing in the 'finished' callback.
    // The contents of 'results' will be available as $results in the
    // 'finished' function updateVisibilityFinishedCallback().
    if ($entity instanceof Post) {
      $default_visibility = self::calculateVisibility($entity->getVisibility(), $new_options);
      $entity->setVisibility($default_visibility);
      $entity->save();
    }
    if ($entity instanceof Node && $entity->hasField('field_content_visibility')) {
      $default_visibility = self::calculateVisibility($entity->getFieldValue('field_content_visibility', 'value'), $new_options);
      $entity->set('field_content_visibility', $default_visibility);
      $entity->save();
    }

    // Make sure our GroupContent referenced entities also get invalidated.
    $tags = $entity->getCacheTagsToInvalidate();
    Cache::invalidateTags($tags);

    // Add referenced entity to results. Might want to add it to the result.
    $context['results'][] = $entity;

    // Optional message displayed under the progressbar.
    $context['message'] = t('Updating group content (@id)', ['@id' => $entity->id()]);
  }

  /**
   * Callback for finished batch events.
   *
   * @param bool $success
   *   TRUE if the update was fully succeeded.
   * @param array $results
   *   Contains individual results per operation.
   * @param array $operations
   *   Contains the unprocessed operations that failed or weren't touched yet.
   */
  public static function updateVisibilityFinishedCallback($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $messenger->addStatus(t('Visibility of content item(s) updated.'));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * Load all Posts based on a certain group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group where we should check our posts for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\social_post\Entity\Post[]
   *   Returning the Posts that are part of a Group.
   */
  public static function getPostsFromGroup(GroupInterface $group) {
    $posts = &drupal_static(__FUNCTION__);
    if (!isset($posts)) {
      // Posts aren't marked as group content so we load them separately.
      $query = \Drupal::database()->select('post__field_recipient_group', 'pst');
      $query->addField('pst', 'entity_id');
      $query->condition('pst.field_recipient_group_target_id', $group->id());
      $query->execute()->fetchAll();

      $post_keys = $query->execute()->fetchAllAssoc('entity_id');

      // Store all the post entity ids.
      $post_ids = array_keys($post_keys);

      $posts = Post::loadMultiple($post_ids);
    }

    return $posts;
  }

  /**
   * Calculate the new visibility options.
   *
   * @param string $current_visibility
   *   The current visibility.
   * @param array $new_options
   *   The new options to choose from.
   *
   * @return string
   *   The new visibility.
   */
  public static function calculateVisibility($current_visibility, array $new_options) {
    // If there is only one option just return that one.
    if (count($new_options) === 1) {
      return reset($new_options)['value'];
    }

    /** @var \Drupal\user\RoleInterface $role */
    $role = \Drupal::entityTypeManager()->getStorage('user_role')->load($current_visibility);
    if ($role instanceof RoleInterface) {
      return reset($new_options)['value'];
    }

    $visibility = '';
    $option_values = array_column($new_options, 'value');
    // Calculate new options based on what it was before editting.
    switch ($current_visibility) {
      case 'community':
        $visibility = 'public';
        if (in_array('group', $option_values)) {
          $visibility = 'group';
        }
        break;

      case 'public':
        $visibility = 'group';
        if (in_array('community', $option_values)) {
          $visibility = 'community';
        }
        break;

      case 'group':
        $visibility = 'public';
        if (in_array('community', $option_values)) {
          $visibility = 'community';
        }
        break;
    }

    return $visibility;
  }

}
