<?php

declare(strict_types=1);

namespace Drupal\social_group\CacheContext;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_group\SocialGroupHelperService;

/**
 * Defines a cache context for user group memberships.
 *
 * This cache context varies based on the groups a user is a member of
 * and whether they manage all groups.
 */
class SocialGroupUserMembershipCacheContext implements CacheContextInterface {

  /**
   * Constructs a new SocialGroupUserMembershipCacheContext class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\social_group\SocialGroupHelperService $socialGroupHelper
   *   The social group helper service.
   */
  public function __construct(
    protected AccountInterface $user,
    protected SocialGroupHelperService $socialGroupHelper,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): TranslatableMarkup {
    return t('User group memberships');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    if ($this->user->hasPermission('manage all groups')) {
      return 'group_membership.all';
    }

    $groups = $this->socialGroupHelper->getAllGroupsForUser((int) $this->user->id());
    if (empty($groups)) {
      return 'group_membership.none';
    }

    return implode(',', $groups);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return new CacheableMetadata();
  }

}
