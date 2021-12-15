<?php

namespace Drupal\social_group_invite\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\ginvite\GroupInvitationLoaderInterface;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Invite notification' block.
 *
 * @Block(
 *   id = "membership_invite_notification",
 *   admin_label = @Translation("Group membership invite notification"),
 * )
 */
class SocialGroupInviteNotificationBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

  /**
   * Invitation Loader.
   *
   * @var \Drupal\ginvite\GroupInvitationLoaderInterface
   */
  protected $inviteLoader;

  /**
   * Constructs SocialGroupInviteNotificationBlock.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account entity.
   * @param \Drupal\ginvite\GroupInvitationLoaderInterface $inviteLoader
   *   The invite loader.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation
   *   The translation manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $account,
    GroupInvitationLoaderInterface $inviteLoader,
    TranslationManager $translation
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->group = _social_group_get_current_group();
    $this->inviteLoader = $inviteLoader;
    $this->translation = $translation;
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
      $container->get('ginvite.invitation_loader'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Only when group invite is installed.
    if (!$this->group->getGroupType()->hasContentPlugin('group_invitation')) {
      return [];
    }

    // Check if the user (entity_id) has a pending invite for the group.
    $properties = [
      'entity_id' => $this->account->id(),
      'gid' => $this->group->id(),
      'invitation_status' => GroupInvitation::INVITATION_PENDING,
    ];

    // No pending invites for the current.
    if (empty($this->inviteLoader->loadByProperties($properties))) {
      return [];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('You have been invited to join this group'),
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
    $is_logged_in = $account->isAuthenticated();

    return AccessResult::allowedIf($is_group_page && $is_logged_in);
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
      'route.group',
    ]);
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'group_content_list:entity:' . $this->account->id(),
      'group_content_list:plugin:group_invitation:entity:' . $this->account->id(),
      'group:' . $this->group->id(),
    ]);
  }

}
