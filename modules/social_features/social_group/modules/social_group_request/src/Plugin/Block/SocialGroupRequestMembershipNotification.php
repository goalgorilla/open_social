<?php

namespace Drupal\social_group_request\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\social_group\Entity\Group;
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
   * Translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation
   *   The translation manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $account,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationManager $translation,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->group = _social_group_get_current_group();
    $this->entityTypeManager = $entity_type_manager;
    $this->translation = $translation;
    $this->moduleHandler = $module_handler;
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
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->group->getGroupType()->hasContentPlugin('group_membership_request')) {
      return [];
    }

    $group_types = ['flexible_group'];
    $this->moduleHandler->alter('social_group_request', $group_types);

    if (in_array($this->group->getGroupType()->id(), $group_types)) {
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
      '#value' => $this->t('There @link to join this group.', [
        '@link' => Link::fromTextAndUrl(
          $this->translation->formatPlural($requests, 'is (1) new request', 'are (@count) new requests'),
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
    if ($this->group instanceof Group) {
      $is_group_manager = $this->group->hasPermission('administer members', $account);
      return AccessResult::allowedIf($is_group_page && $is_group_manager);
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    // Ensure the context keeps track of the URL
    // so we don't see the message on every group.
    $contexts = Cache::mergeContexts($contexts, [
      'url',
      'user.permissions',
      'route.group',
    ]);
    return $contexts;
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
