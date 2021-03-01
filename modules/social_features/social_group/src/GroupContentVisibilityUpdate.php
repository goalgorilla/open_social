<?php

namespace Drupal\social_group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\Group;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\Core\Cache\Cache;
use Drupal\social_post\Entity\Post;
use Drupal\node\Entity\Node;

/**
 * Class GroupContentVisibilityUpdate.
 *
 * @package Drupal\social_group
 */
class GroupContentVisibilityUpdate {

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
   * @param string $new_type
   *   The Group's new group type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchUpdateGroupContentVisibility(GroupInterface $group, $new_type) {
    // Set it up as a batch. We need to update visibility.
    // Load all the GroupContentEntities from Post to Memberships to content.
    $entities = $group->getContentEntities();

    $posts = self::getPostsFromGroup($group);
    foreach ($posts as $pid => $post) {
      $entities[] = $post;
    }
    $memberships = $group->getMembers();
    foreach ($memberships as $member) {
      $entities[] = $member;
    }

    $num_operations = count($entities);
    $operations = [];

    // Update Memberships & ContentVisibility.
    // As per documentation each entity has it's own operation.
    for ($i = 0; $i < $num_operations; $i++) {
      $operations[] = [
        '\Drupal\social_group\GroupContentVisibilityUpdate::updateVisibility',
        [
          $entities[$i],
          $new_type,
        ],
      ];
    }

    // Provide all the operations and the finish callback to our batch.
    $batch = [
      'title' => t('Updating Group Content...'),
      'operations' => $operations,
      'finished' => '\Drupal\social_group\GroupContentVisibilityUpdate::updateVisibilityFinishedCallback',
    ];

    batch_set($batch);

    $group->set('type', $new_type);
    $group->save();
  }

  /**
   * Update visibility for all Group Content based on a new group type.
   *
   * @param \Drupal\node\Entity\Node|\Drupal\social_post\Entity\Post|\Drupal\group\GroupMembership|\Drupal\group\Entity\GroupContentInterface $entity
   *   The content we are updating.
   * @param string $new_type
   *   The new Group type.
   * @param array $context
   *   Passed on by reference.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateVisibility($entity, $new_type, array &$context) {
    // Find the corresponding visibility for the new group_type.
    $default_visibility = SocialGroupHelperService::getDefaultGroupVisibility($new_type);

    // Store some results for post-processing in the 'finished' callback.
    // The contents of 'results' will be available as $results in the
    // 'finished' function updateVisibilityFinishedCallback().
    if ($entity instanceof Post) {
      $entity->setVisibility($default_visibility);
      $entity->save();
    }
    if ($entity instanceof Node) {
      $entity->set('field_content_visibility', $default_visibility);
      $entity->save();
    }
    // For GroupMembers we have to update the GroupContent.
    if ($entity instanceof GroupMembership) {
      $new_group_type = $new_type . '-group_membership';
      $membershipEntity = $entity->getGroupContent();
      $membershipEntity->set('type', $new_group_type);
      $membershipEntity->save();
      $entity = $membershipEntity;
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
      $messenger->addStatus(t('Visibility of @count content item(s) updated.', ['@count' => count($results)]));
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

}
