<?php

namespace Drupal\social_group_request\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\social_group\JoinManagerInterface;
use Drupal\social_group\SocialGroupInterface;
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
   * The group entity object.
   */
  protected ?SocialGroupInterface $group;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The join manager.
   */
  private JoinManagerInterface $joinManager;

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
   * @param \Drupal\social_group\JoinManagerInterface $join_manager
   *   The join manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $account,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationManager $translation,
    JoinManagerInterface $join_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->group = _social_group_get_current_group();
    $this->entityTypeManager = $entity_type_manager;
    $this->setStringTranslation($translation);
    $this->joinManager = $join_manager;
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
      $container->get('plugin.manager.social_group.join'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->group === NULL) {
      return [];
    }

    $group_type = $this->group->getGroupType();

    if (!$group_type->hasContentPlugin('group_membership_request')) {
      return [];
    }

    /** @var string $bundle */
    $bundle = $group_type->id();

    if (
      $this->joinManager->hasMethod($bundle, 'request') &&
      $this->group->hasField('field_group_allowed_join_method')
    ) {
      $join_methods = $this->group->field_group_allowed_join_method->getValue();

      if (!in_array('request', array_column($join_methods, 'value'))) {
        return [];
      }
    }
    else {
      $allow_request = $this->group->allow_request;

      if ($allow_request->isEmpty() || $allow_request->value == 0) {
        return [];
      }
    }

    $content_type_config_id = $group_type
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $requests = (int) $this->entityTypeManager->getStorage('group_content')
      ->getQuery()
      ->condition('type', $content_type_config_id)
      ->condition('gid', $this->group->id())
      ->condition('grequest_status', GroupMembershipRequest::REQUEST_PENDING)
      ->count()
      ->execute();

    if ($requests === 0) {
      return [];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('There @link to join this group.', [
        '@link' => Link::fromTextAndUrl(
          $this->getStringTranslation()->formatPlural(
            $requests,
            'is (1) new request',
            'are (@count) new requests',
          ),
          Url::fromRoute(
            'view.group_pending_members.membership_requests',
            ['arg_0' => $this->group->id()],
          ),
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
    if ($this->group === NULL) {
      $access = AccessResult::forbidden();
    }
    else {
      $access = AccessResult::allowedIf(
        $this->group->hasPermission('administer members', $account),
      );
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Ensure the context keeps track of the URL, so we don't see the message on
    // every group.
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'url',
      'user.permissions',
      'route.group',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    if ($this->group !== NULL) {
      $tags = Cache::mergeTags($tags, [
        'request-membership:' . $this->group->id(),
        'group:' . $this->group->id(),
      ]);
    }

    return $tags;
  }

}
