<?php

namespace Drupal\social_group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\Group;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social_post\Entity\Post;
use Drupal\node\Entity\Node;
use Drupal\group\GroupMembershipLoader;

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, GroupMembershipLoader $memberLoader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Update Group content after Group changed
   *
   * @param \Drupal\group\Entity\Group $group
   *   The Group we've updated.
   * @param string $new_type
   *   The Group's new group type
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function batchUpdateGroupContentVisibility(Group $group, $new_type) {
    // Set it up as a batch. We need to update visibility.
    // Load all the GroupContentEntities from Post to Memberships to content.
    $entities = $group->getContentEntities();

    $posts = self::getPostsFromGroup($group);
    foreach ($posts as $pid => $post ) {
      $entities[] = $post;
    }
    $memberships = $group->getMembers();

    $num_operations = count($entities);
    $operations = array();

    // Update Memberships & ContentVisibility.
    // As per documentation each entity has it's own operation.
    for ($i = 0; $i < $num_operations; $i++) {
      $operations[] = array(
        '\Drupal\social_group\GroupContentVisibilityUpdate::updateVisibility',
        array(
          $entities[$i],
          $new_type,
        ),
      );
    }

    // Provide all the operations and the finish callback to our batch.
    $batch = array(
      'title' => t('Updating Group Content...'),
      'operations' => $operations,
      'finished' => '\Drupal\social_group\GroupContentVisibilityUpdate::updateVisibilityFinishedCallback',
    );

    batch_set($batch);

    $group->set('type', $new_type);
    $group->save();

    $content = array();
    foreach ($memberships as $member) {
      $group_content = $member->getGroupContent();
      $new_group_type = $new_type . '-group_membership';
      $group_content->set('type', $new_group_type);
      $content[] = $group_content->save();
    }
  }

  /**
   * Update visibility for all Group Content based on a new group type.
   * Memberships will be dealt with separately.
   *
   * @param \Drupal\node\Entity\Node|\Drupal\social_post\Entity\Post
   *   The content we are updating.
   * @param string $new_type
   *   The new Group type.
   * @param array $context
   *   Passed on.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateVisibility($entity, $new_type, &$context) {
    // Find the corresponding visibility for the new_group_type
    $default_visibility = SocialGroupHelperService::getDefaultGroupVisibility($new_type);

    // Store some results for post-processing in the 'finished' callback.
    // The contents of 'results' will be available as $results in the
    // 'finished' function updateVisibilityFinishedCallback().
    if ($entity instanceof Post) {
      $entity->setVisibility($default_visibility);
      $context['results'][] = $entity->save();
    }
    if ($entity instanceof Node) {
      $entity->set('field_content_visibility', $default_visibility);
      $context['results'][] = $entity->save();
    }
//    Skipping members for now.
//    if ($entity instanceof GroupMembership) {
//      $membership = $this->memberLoader->load($)
//    }

    $context['results'][] = $entity->save();

    // Optional message displayed under the progressbar.
    $context['message'] = t('Updating Entity ID "@type"', array('@type' => $entity->id()));
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
  function updateVisibilityFinishedCallback($success, $results, $operations) {
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      drupal_set_message(t('@count results processed.', array('@count' => count($results))));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      drupal_set_message(
        t('An error occurred while processing @operation with arguments : @args',
          array(
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          )
        ),
        'error'
      );
    }
  }

  /**
   * Load all Posts based on a certain group.
   *
   * @param $group \Drupal\group\Entity\Group
   *   The Group where we should check our posts for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\social_post\Entity\Post[]
   */
  public static function getPostsFromGroup($group) {
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

      $posts = \Drupal\social_post\Entity\Post::loadMultiple($post_ids);
    }

    return $posts;
  }

}
