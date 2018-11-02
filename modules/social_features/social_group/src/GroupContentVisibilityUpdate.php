<?php

namespace Drupal\social_group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social_group\SocialGroupHelperService;

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
   * Update Group content after Group changed
   *
   * @param \Drupal\group\Entity\Group $group
   *   The Group we've updated.
   * @param string $new_type
   *   The Group's new group type
   */
  public static function batchUpdateGroupContentVisibility(Group $group, $new_type) {
    // Set it up as a batch. We need to update visibility.
    $batch = array(
      'title' => t('Updating Group Content...'),
      'operations' => array(
        array(
          '\Drupal\social_group\GroupContentVisibilityUpdate::updateVisibility',
          array($group),
          array($new_type),
        ),
      ),
      'finished' => '\Drupal\social_group\GroupContentVisibilityUpdate::updateVisibilityFinishedCallback',
    );

    batch_set($batch);
  }

  /**
   * Update visibility for all Group Content based on a new group type.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The Group where we are updating content from.
   * @param string $new_type
   *   The new Group type.
   * @param array $context
   *   Passed by reference.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function updateVisibility(Group $group, $new_type, &$context) {
    // Load all the GroupContentEntities from Post to Memberships to content.
    $entities = $group->getContentEntities();

    // Find the corresponding visibility for the new_group_type
    $default_visibility = SocialGroupHelperService::getDefaultGroupVisibility($new_type);

    $message = t('Updating Group Content..');
    $results = array(
      'new_type' => $new_type,
    );

    foreach ($entities as $id) {
      $group_content = GroupContent::load($id);
      // If post / entity we update the visibility.
      $results['count'][] = $node->delete();
      // If User we update the membership.
    }

    $context['message'] = $message;
    $context['results'] = $results;
  }

  function updateVisibilityFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results['count']),
        'One GroupContent item processed.', '@count GroupContent items processed.'
      );

      $new_type = $results['new_type'];

      // Save the group as well. Otherwise we can't make sure the content and
      // it's visibility is handled correctly.
      $group->type = $new_type;
      $group->save();
    }
    else {
      $message = t('The Group type edit went wrong, no entities were updated in the process.');
    }

    drupal_set_message($message);
  }


}
