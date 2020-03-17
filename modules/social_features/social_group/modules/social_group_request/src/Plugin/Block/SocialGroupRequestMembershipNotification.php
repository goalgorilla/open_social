<?php

namespace Drupal\social_group_request\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Fax' block.
 *
 * @Block(
 *   id = "membership_requests_notification",
 *   admin_label = @Translation("Membership requests notification"),
 * )
 */
class SocialGroupRequestMembershipNotification extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var AccountInterface $account
   */
  protected $account;

  /**
   * @var \Drupal\group\Entity\GroupInterface $group
   */
  protected $group;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->group = _social_group_get_current_group();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
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

    $requests = \Drupal::entityQuery('group_content')
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
