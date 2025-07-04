<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_group\GroupMuteNotify;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupContentInMyGroupActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "group_content_in_my_group_activity_context",
 *   label = @Translation("Group content in my group activity context"),
 * )
 */
class GroupContentInMyGroupActivityContext extends ActivityContextBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
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
    AccountProxyInterface $current_user,
    GroupMuteNotify $group_mute_notify,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->currentUser = $current_user;
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
      $container->get('current_user'),
      $container->get('social_group.group_mute_notify')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, int $last_id, int $limit): array {
    $recipients = [];

    if (!empty($data['related_object'])) {
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);

      $group_content = $this->entityTypeManager->getStorage('group_content')
        ->load($referenced_entity['target_id']);

      // It could happen that a notification has been queued but the content
      // has since been deleted. In that case we can find no additional
      // recipients.
      if ($group_content === NULL) {
        return $recipients;
      }

      $group = $group_content->getGroup();

      $memberships = $group->getMembers($group->bundle() . '-group_manager');

      // List of managers which shouldn't receive notifications.
      $account_ids = [
        // The current user when is a manager.
        $this->currentUser->id(),
        // New group member with the "Group manager" role.
        $group_content->getEntity()->id(),
      ];

      /** @var \Drupal\group\GroupMembership $membership */
      foreach ($memberships as $membership) {
        if (!in_array($membership->getUser()->id(), $account_ids) && !$this->groupMuteNotify->groupNotifyIsMuted($group, $membership->getUser())) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $membership->getUser()->id(),
          ];
        }
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity): bool {
    return $entity->getEntityTypeId() === 'group_content';
  }

}
