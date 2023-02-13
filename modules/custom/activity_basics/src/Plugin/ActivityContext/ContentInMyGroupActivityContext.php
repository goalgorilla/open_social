<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\GroupMuteNotify;
use Drupal\social_post\Entity\PostInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ContentInMyGroupActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "content_in_my_group_activity_context",
 *   label = @Translation("Content in my group activity context"),
 * )
 */
class ContentInMyGroupActivityContext extends ActivityContextBase {

  /**
   * The group mute notifications.
   *
   * @var \Drupal\social_group\GroupMuteNotify
   */
  protected $groupMuteNotify;

  /**
   * Constructs a GroupContentInMyGroupActivityContext object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\Sql\QueryFactory $entity_query
   *   The query factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\activity_creator\ActivityFactory $activity_factory
   *   The activity factory service.
   * @param \Drupal\social_group\GroupMuteNotify $group_mute_notify
   *   The group mute notifications.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    GroupMuteNotify $group_mute_notify
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->groupMuteNotify = $group_mute_notify;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query.sql'),
      $container->get('entity_type.manager'),
      $container->get('activity_creator.activity_factory'),
      $container->get('social_group.group_mute_notify')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, int $last_id, int $limit): array {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);
      $owner_id = '';

      if (isset($referenced_entity['target_type']) && $referenced_entity['target_type'] === 'post') {
        try {
          $post = $this->entityTypeManager->getStorage('post')
            ->load($referenced_entity['target_id']);
        }
        catch (PluginNotFoundException $exception) {
          return $recipients;
        }

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if ($post === NULL) {
          return $recipients;
        }

        $gid = $post->get('field_recipient_group')->getValue();
        $owner_id = $post->getOwnerId();
      }
      else {
        $group_content = $this->entityTypeManager->getStorage('group_content')
          ->load($referenced_entity['target_id']);

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if ($group_content === NULL) {
          return $recipients;
        }

        $node = $group_content->getEntity();

        if ($node instanceof NodeInterface) {
          $owner_id = $node->getOwnerId();

          if (!$node->isPublished()) {
            return $recipients;
          }
        }

        $gid = $group_content->get('gid')->getValue();
      }

      if ($gid && isset($gid[0]['target_id'])) {
        $target_id = $gid[0]['target_id'];

        $recipients[] = [
          'target_type' => 'group',
          'target_id' => $target_id,
        ];

        $group = $this->entityTypeManager->getStorage('group')
          ->load($target_id);

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if (!$group instanceof GroupInterface) {
          return $recipients;
        }

        $memberships = $group->getMembers();

        /** @var \Drupal\group\GroupMembership $membership */
        foreach ($memberships as $membership) {
          // Check if this is not the created user and didn't mute the group
          // notifications.
          // There can be incidences where even if the user was deleted
          // its membership data was left in the table
          // group_content_field_data, so, it is necessary to check
          // if the user actually exists in system.
          $group_user = $membership->getUser();
          if (
            $group_user !== NULL &&
            $owner_id != $membership->getUser()->id() &&
            !$this->groupMuteNotify->groupNotifyIsMuted($group, $membership->getUser())
          ) {
            $recipients[] = [
              'target_type' => 'user',
              'target_id' => $membership->getUser()->id(),
            ];
          }
        }
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity): bool {
    if ($entity instanceof GroupContentInterface) {
      return TRUE;
    }

    if ($entity instanceof PostInterface) {
      return $entity->hasField("field_recipient_group") && !$entity->get("field_recipient_group")->isEmpty();
    }

    return FALSE;
  }

}
