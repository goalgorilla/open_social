<?php

namespace Drupal\social_group_request\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Membership requests notification' block.
 *
 * @Block(
 *   id = "membership_requests_notification",
 *   admin_label = @Translation("Membership requests notification"),
 * )
 */
class SocialGroupRequestMembershipNotification extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * User account entity.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs SocialGroupRequestMembershipNotification.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account entity.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->group = _social_group_get_current_group();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->group->getGroupType()->hasContentPlugin('group_membership_request')) {
      return [];
    }

    if ($this->group->getGroupType()->id() === 'flexible_group') {
      $join_methods = $this->group->get('field_group_allowed_join_method')->getValue();
      $request_option = in_array('request', array_column($join_methods, 'value'), FALSE);
      if (!$request_option) {
        return [];
      }
    }
    else {
      $allow_request = $this->group->get('allow_request');
      if ($allow_request->isEmpty() || $allow_request->value == 0) {
        return [];
      }
    }

    $contentTypeConfigId = $this->group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $requests = $this->entityTypeManager->getStorage('group_content')->getQuery()
      ->condition('type', $contentTypeConfigId)
      ->condition('gid', $this->group->id())
      ->condition('grequest_status', GroupMembershipRequest::REQUEST_PENDING)
      ->count()
      ->execute();

    if (!$requests) {
      return [];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('There are @link to join this group.', [
        '@link' => Link::fromTextAndUrl(
          $this->t('(@count) new requests', [
            '@count' => $requests,
          ]),
          Url::fromRoute('view.group_pending_members.membership_requests', ['arg_0' => $this->group->id()])
        )->toString(),
      ]),
      '#attributes' => [
        'class' => [
          'alert',
          'alert-warning',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $is_group_page = isset($this->group);
    $is_group_manager = $account->hasPermission('administer members');

    return AccessResult::allowedIf($is_group_page && $is_group_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'request-membership:' . $this->group->id(),
      'group:' . $this->group->id(),
    ]);
  }

}
