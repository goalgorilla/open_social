<?php

namespace Drupal\social_profile\Service;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Link;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\GroupAffiliation;

/**
 * Decides whether and how a primary affiliation may be displayed.
 *
 * The affiliation data API (::getPrimaryAffiliation() and friends) deliberately
 * stays ungated so that SCIM, GraphQL, the CSV export and the EDA keep working
 * from the command line. That API is therefore a superset of what may be shown
 * to a viewer: this service subtracts the display rules on top of it.
 */
final class AffiliationDisplay {

  /**
   * The affiliation type used for affiliations that are not a platform group.
   */
  protected const EXTERNAL_AFFILIATION_TYPE = 'External';

  /**
   * Constructs an AffiliationDisplay object.
   *
   * @param \Drupal\social_profile\GroupAffiliation $groupAffiliation
   *   The group affiliation service.
   */
  public function __construct(
    protected GroupAffiliation $groupAffiliation,
  ) {}

  /**
   * Returns the primary affiliation if the current user may see it.
   *
   * @param \Drupal\social_profile\Entity\ProfileAffiliationInterface $profile
   *   The profile to read the affiliation from.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface|null $cacheability
   *   (optional) Collects the cacheability of the decision. Callers that render
   *   the result must bubble it, the answer varies per viewer.
   *
   * @return array
   *   The affiliation as returned by ::getPrimaryAffiliation(), plus an
   *   "affiliation_group" key holding the affiliated group when the affiliation
   *   is a platform group. An empty array when it must not be displayed.
   */
  public function getDisplayableAffiliation(
    ProfileAffiliationInterface $profile,
    ?RefinableCacheableDependencyInterface $cacheability = NULL,
  ): array {
    // Seeded before any early return: this one tag invalidates every answer the
    // GroupAffiliation service can give (the global toggle read by
    // ::getPrimaryAffiliation(), ::isGroupAffiliationEnabled() and
    // ::getAffiliationEnabledGroupTypes()). None of them is invalidated by a
    // config tag, so hand-writing config:social_profile.settings is wrong.
    $cacheability?->addCacheTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    // Field access is evaluated per account rather than per permission set, two
    // accounts with identical permissions can get a different answer.
    $cacheability?->addCacheContexts(['user']);
    $cacheability?->addCacheableDependency($profile);

    $affiliation = $profile->getPrimaryAffiliation();
    if (empty($affiliation['affiliation_name'])) {
      return [];
    }

    if ($profile->getPrimaryAffiliationType() === self::EXTERNAL_AFFILIATION_TYPE) {
      // With no group type affiliation-enabled, the affiliation feature is
      // effectively off for display, so the non-platform affiliation is hidden
      // too.
      // @see \Drupal\social_profile\GroupAffiliation::isGroupAffiliationEnabled()
      if (!$this->groupAffiliation->isGroupAffiliationEnabled()) {
        return [];
      }

      // An inaccessible group silently degrades to the external paragraph, so
      // the external branch needs both of its own gates.
      // @see \Drupal\social_profile\Entity\Bundle\SocialProfile::getPrimaryAffiliation()
      $enabled_access = $profile->get('field_enable_other_affiliations')->access('view', NULL, TRUE);
      $values_access = $profile->get('field_other_affiliations')->access('view', NULL, TRUE);
      $cacheability?->addCacheableDependency($enabled_access);
      $cacheability?->addCacheableDependency($values_access);

      if (!$enabled_access->isAllowed() || !$values_access->isAllowed()) {
        return [];
      }

      return $affiliation;
    }

    $group = $profile->get(GroupAffiliation::AFFILIATION_FIELD_NAME)->entity;
    if (!$group instanceof GroupInterface) {
      return [];
    }
    $cacheability?->addCacheableDependency($group);
    $cacheability?->addCacheableDependency($group->getGroupType());

    // Logically redundant, ::getPrimaryAffiliation() already filtered on this
    // very check. It is repeated here in object form purely to recover the
    // cacheability the upstream boolean call throws away, the same reason the
    // field checks below ask for an access result object. Without it a
    // membership change invalidates the group relationship list tags but not
    // group:N, so a viewer removed from a secret group keeps seeing its name.
    // @todo Drop this once PROD-34701 makes the upstream call return an
    //   access result object.
    // @see \Drupal\social_profile\Entity\Bundle\SocialProfile::getPrimaryAffiliation()
    $group_access = $group->access('view', NULL, TRUE);
    $cacheability?->addCacheableDependency($group_access);
    if (!$group_access->isAllowed()) {
      return [];
    }

    // Affiliation may be switched off for this specific group type. Asking the
    // service whether group affiliation is enabled at all is not enough, that
    // would still show an affiliation coming from a disabled group type.
    if (!isset($this->groupAffiliation->getAffiliationEnabledGroupTypes()[$group->bundle()])) {
      return [];
    }

    // Calling access() on the field item list rather than through the profile
    // entity avoids the CLI shortcut in SocialProfile and returns the access
    // result object whose cacheability we need.
    $field_access = $profile->get(GroupAffiliation::AFFILIATION_FIELD_NAME)->access('view', NULL, TRUE);
    $cacheability?->addCacheableDependency($field_access);
    if (!$field_access->isAllowed()) {
      return [];
    }

    $affiliation['affiliation_group'] = $group;

    return $affiliation;
  }

  /**
   * Builds the affiliation label as a render array.
   *
   * @param \Drupal\social_profile\Entity\ProfileAffiliationInterface $profile
   *   The profile to read the affiliation from.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface|null $cacheability
   *   (optional) Collects the cacheability of the decision. Pass it whenever
   *   the caller renders in isolation or drops the render array, hiding the
   *   affiliation is as viewer specific as showing it and an empty return
   *   value would otherwise be cached as if it were the same for everyone.
   *
   * @return array
   *   A render array linking to the affiliated group, or plain text for an
   *   external affiliation. An empty array when the affiliation must not be
   *   displayed.
   */
  public function buildLabel(
    ProfileAffiliationInterface $profile,
    ?RefinableCacheableDependencyInterface $cacheability = NULL,
  ): array {
    $own_cacheability = new CacheableMetadata();
    $affiliation = $this->getDisplayableAffiliation($profile, $own_cacheability);
    $cacheability?->addCacheableDependency($own_cacheability);

    if ($affiliation === []) {
      return [];
    }

    $group = $affiliation['affiliation_group'] ?? NULL;
    $build = $group instanceof GroupInterface
      ? Link::fromTextAndUrl($affiliation['affiliation_name'], $group->toUrl())->toRenderable()
      : ['#plain_text' => $affiliation['affiliation_name']];
    $own_cacheability->applyTo($build);

    return $build;
  }

  /**
   * Returns the affiliation label as unescaped plain text.
   *
   * @param \Drupal\social_profile\Entity\ProfileAffiliationInterface $profile
   *   The profile to read the affiliation from.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface|null $cacheability
   *   (optional) Collects the cacheability of the decision.
   *
   * @return string
   *   The affiliation name, or an empty string when it must not be displayed.
   */
  public function getPlainLabel(
    ProfileAffiliationInterface $profile,
    ?RefinableCacheableDependencyInterface $cacheability = NULL,
  ): string {
    $affiliation = $this->getDisplayableAffiliation($profile, $cacheability);

    return (string) ($affiliation['affiliation_name'] ?? '');
  }

}
