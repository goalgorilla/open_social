<?php

declare(strict_types=1);

namespace Drupal\social_group;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides configurability to bundle classes for membership request behaviour.
 *
 * Bundle classes for the Group entity can implement this class to affect how
 * the social_group_request module implements certain behaviour around
 * membership requests for objects built on top of the group module.
 *
 * When implementing this interface on a bundle class you should add the
 * following annotation `@implements GroupMembershipRequestableInterface<self>`.
 *
 * @template TBundleClass of GroupInterface
 */
interface GroupMembershipRequestableInterface {

  /**
   * The title that should be shown on the request membership page/dialog.
   *
   * @param TBundleClass $group
   *   The group for which membership is being requested.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   Either the title as MarkupInterface or NULL in case the default title
   *   should be used.
   */
  public function requestMembershipTitle(GroupInterface $group): ?TranslatableMarkup;

}
