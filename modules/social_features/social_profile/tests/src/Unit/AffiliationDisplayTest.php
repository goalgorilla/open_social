<?php

namespace Drupal\Tests\social_profile\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\GroupAffiliation;
use Drupal\social_profile\Service\AffiliationDisplay;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the affiliation display rules.
 *
 * @coversDefaultClass \Drupal\social_profile\Service\AffiliationDisplay
 * @group social_profile
 */
class AffiliationDisplayTest extends UnitTestCase {

  /**
   * The group affiliation service.
   */
  protected GroupAffiliation&MockObject $groupAffiliation;

  /**
   * The service under test.
   */
  protected AffiliationDisplay $affiliationDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Cache::mergeContexts() asserts its tokens against the container.
    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    $this->groupAffiliation = $this->createMock(GroupAffiliation::class);
    $this->affiliationDisplay = new AffiliationDisplay($this->groupAffiliation);
  }

  /**
   * A profile without an affiliation is never displayable.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenGetterReturnsNothing(): void {
    $profile = $this->createProfile([]);

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * An affiliation without a name is never displayable.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenAffiliationHasNoName(): void {
    $profile = $this->createProfile(['affiliation_name' => '', 'affiliation_type' => 'External']);

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * A visible platform affiliation resolves to the affiliated group.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testPlatformAffiliationIsDisplayable(): void {
    $group = $this->createGroup('flexible_group');
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed(), $group)],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $affiliation = $this->affiliationDisplay->getDisplayableAffiliation($profile);

    $this->assertSame('Acme Corp', $affiliation['affiliation_name']);
    $this->assertSame($group, $affiliation['affiliation_group']);
  }

  /**
   * Affiliation switched off for the group's own type hides the affiliation.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenGroupTypeNotAffiliationEnabled(): void {
    $group = $this->createGroup('flexible_group');
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed(), $group)],
    );
    // Another group type has affiliation enabled, this one does not.
    $this->allowAffiliationForGroupTypes(['closed_group']);

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * A group affiliation field the viewer may not see hides the affiliation.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenGroupFieldAccessForbidden(): void {
    $group = $this->createGroup('flexible_group');
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::forbidden(), $group)],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * A group the viewer may not view hides the affiliation.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenGroupViewAccessForbidden(): void {
    $group = $this->createGroup('flexible_group', [], [], AccessResult::forbidden());
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed(), $group)],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * The group view decision carries its own cacheability into the answer.
   *
   * ::getPrimaryAffiliation() evaluates the same check in boolean form and
   * drops its tags, so a membership change would invalidate the group
   * relationship list tags and nothing else. Re-doing the check in object form
   * is what puts those tags back on the rendered affiliation.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testGroupViewAccessCacheabilityIsCollected(): void {
    $access = AccessResult::allowed()
      ->addCacheTags(['group_content_list:plugin:group_membership:entity:11'])
      ->addCacheContexts(['user.group_permissions']);
    $group = $this->createGroup('flexible_group', ['group:7'], [], $access);
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed(), $group)],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $cacheability = new CacheableMetadata();
    $this->affiliationDisplay->getDisplayableAffiliation($profile, $cacheability);

    $this->assertContains('group_content_list:plugin:group_membership:entity:11', $cacheability->getCacheTags());
    $this->assertContains('user.group_permissions', $cacheability->getCacheContexts());
  }

  /**
   * A forbidden group view decision still bubbles its cacheability.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testForbiddenGroupViewAccessCacheabilityIsCollected(): void {
    $access = AccessResult::forbidden()
      ->addCacheTags(['group_content_list:plugin:group_membership:entity:11']);
    $group = $this->createGroup('flexible_group', ['group:7'], [], $access);
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed(), $group)],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $cacheability = new CacheableMetadata();

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile, $cacheability));
    $this->assertContains('group_content_list:plugin:group_membership:entity:11', $cacheability->getCacheTags());
    $this->assertContains('group:7', $cacheability->getCacheTags());
  }

  /**
   * A platform affiliation whose group cannot be resolved fails closed.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenGroupEntityMissing(): void {
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed(), NULL)],
    );

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * A visible external affiliation is displayable without a group.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testExternalAffiliationIsDisplayable(): void {
    $this->enableGroupAffiliation();
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'External'],
      [
        'field_enable_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
        'field_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
      ],
    );

    $affiliation = $this->affiliationDisplay->getDisplayableAffiliation($profile);

    $this->assertSame('Acme Corp', $affiliation['affiliation_name']);
    $this->assertArrayNotHasKey('affiliation_group', $affiliation);
  }

  /**
   * With affiliation off for every group type, external affiliations go too.
   *
   * Switching "Enable affiliation for Groups" off on every group type turns
   * the feature off for display as a whole, so the free-text non-platform
   * affiliation must disappear from the streams as well.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenExternalAndNoGroupTypeAffiliationEnabled(): void {
    $this->enableGroupAffiliation(FALSE);
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'External'],
      [
        'field_enable_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
        'field_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
      ],
      ['profile:3'],
    );

    $cacheability = new CacheableMetadata();

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile, $cacheability));
    // The tag that invalidates ::isGroupAffiliationEnabled() is seeded before
    // this early return, so flipping the toggle back on invalidates the answer.
    $this->assertContains(GroupAffiliation::GENERAL_CACHE_TAG, $cacheability->getCacheTags());
    $this->assertContains('user', $cacheability->getCacheContexts());
  }

  /**
   * Disabling the external affiliation field hides the affiliation.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenOtherAffiliationsAccessForbidden(): void {
    $this->enableGroupAffiliation();
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'External'],
      [
        'field_enable_other_affiliations' => $this->createFieldItemList(AccessResult::forbidden()),
        'field_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
      ],
    );

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * Hiding the external affiliation values hides the affiliation.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testEmptyWhenOtherAffiliationsHidden(): void {
    $this->enableGroupAffiliation();
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'External'],
      [
        'field_enable_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
        'field_other_affiliations' => $this->createFieldItemList(AccessResult::forbidden()),
      ],
    );

    $this->assertSame([], $this->affiliationDisplay->getDisplayableAffiliation($profile));
  }

  /**
   * The decision varies per viewer and is invalidated by the affiliation tag.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testCacheabilityIsCollected(): void {
    $group = $this->createGroup('flexible_group', ['group:7'], ['group_context']);
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed()->addCacheTags(['config:social_profile_fields.settings']), $group)],
      ['profile:3'],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $cacheability = new CacheableMetadata();
    $this->affiliationDisplay->getDisplayableAffiliation($profile, $cacheability);

    $this->assertContains('user', $cacheability->getCacheContexts());
    $this->assertContains('group_context', $cacheability->getCacheContexts());
    $this->assertContains(GroupAffiliation::GENERAL_CACHE_TAG, $cacheability->getCacheTags());
    $this->assertContains('profile:3', $cacheability->getCacheTags());
    $this->assertContains('group:7', $cacheability->getCacheTags());
    $this->assertContains('config:social_profile_fields.settings', $cacheability->getCacheTags());
  }

  /**
   * Cacheability collection is optional.
   *
   * @covers ::getDisplayableAffiliation
   */
  public function testCacheabilityIsOptional(): void {
    $this->enableGroupAffiliation();
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'External'],
      [
        'field_enable_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
        'field_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
      ],
    );

    $affiliation = $this->affiliationDisplay->getDisplayableAffiliation($profile, NULL);

    $this->assertSame('Acme Corp', $affiliation['affiliation_name']);
  }

  /**
   * A platform affiliation is rendered as a link to the group.
   *
   * @covers ::buildLabel
   */
  public function testBuildLabelLinksToTheGroup(): void {
    $url = $this->createMock(Url::class);
    $group = $this->createGroup('flexible_group');
    $group->method('toUrl')->willReturn($url);
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::allowed(), $group)],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $build = $this->affiliationDisplay->buildLabel($profile);

    $this->assertSame('link', $build['#type']);
    $this->assertSame('Acme Corp', $build['#title']);
    $this->assertSame($url, $build['#url']);
    $this->assertContains('user', $build['#cache']['contexts']);
    $this->assertContains(GroupAffiliation::GENERAL_CACHE_TAG, $build['#cache']['tags']);
  }

  /**
   * An external affiliation is rendered as escaped plain text.
   *
   * @covers ::buildLabel
   */
  public function testBuildLabelRendersExternalAsPlainText(): void {
    $this->enableGroupAffiliation();
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme & Co <script>', 'affiliation_type' => 'External'],
      [
        'field_enable_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
        'field_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
      ],
    );

    $build = $this->affiliationDisplay->buildLabel($profile);

    $this->assertSame('Acme & Co <script>', $build['#plain_text']);
    $this->assertArrayNotHasKey('#type', $build);
  }

  /**
   * A hidden affiliation produces no render array at all.
   *
   * @covers ::buildLabel
   */
  public function testBuildLabelIsEmptyWhenHidden(): void {
    $profile = $this->createProfile([]);

    $this->assertSame([], $this->affiliationDisplay->buildLabel($profile));
  }

  /**
   * Hiding the affiliation is as viewer specific as showing it.
   *
   * The empty return value must still carry the cacheability of the decision,
   * otherwise it gets cached as if it were the same answer for everyone.
   *
   * @covers ::buildLabel
   */
  public function testBuildLabelBubblesCacheabilityWhenHidden(): void {
    $group = $this->createGroup('flexible_group', ['group:7']);
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'Flexible group'],
      ['field_group_affiliation' => $this->createFieldItemList(AccessResult::forbidden(), $group)],
      ['profile:3'],
    );
    $this->allowAffiliationForGroupTypes(['flexible_group']);

    $cacheability = new CacheableMetadata();

    $this->assertSame([], $this->affiliationDisplay->buildLabel($profile, $cacheability));
    $this->assertContains('user', $cacheability->getCacheContexts());
    $this->assertContains(GroupAffiliation::GENERAL_CACHE_TAG, $cacheability->getCacheTags());
    $this->assertContains('profile:3', $cacheability->getCacheTags());
    $this->assertContains('group:7', $cacheability->getCacheTags());
  }

  /**
   * The plain label is the bare affiliation name, without a separator.
   *
   * @covers ::getPlainLabel
   */
  public function testPlainLabelReturnsTheName(): void {
    $this->enableGroupAffiliation();
    $profile = $this->createProfile(
      ['affiliation_name' => 'Acme Corp', 'affiliation_type' => 'External'],
      [
        'field_enable_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
        'field_other_affiliations' => $this->createFieldItemList(AccessResult::allowed()),
      ],
    );

    $cacheability = new CacheableMetadata();

    $this->assertSame('Acme Corp', $this->affiliationDisplay->getPlainLabel($profile, $cacheability));
    $this->assertContains('user', $cacheability->getCacheContexts());
  }

  /**
   * A hidden affiliation has no plain label.
   *
   * @covers ::getPlainLabel
   */
  public function testPlainLabelIsEmptyWhenHidden(): void {
    $profile = $this->createProfile([]);

    $this->assertSame('', $this->affiliationDisplay->getPlainLabel($profile));
  }

  /**
   * Declares whether any group type at all has affiliation enabled.
   *
   * @param bool $enabled
   *   What ::isGroupAffiliationEnabled() reports.
   */
  protected function enableGroupAffiliation(bool $enabled = TRUE): void {
    $this->groupAffiliation->method('isGroupAffiliationEnabled')->willReturn($enabled);
  }

  /**
   * Declares which group types have affiliation enabled.
   *
   * @param string[] $bundles
   *   The group type ids affiliation is enabled for.
   */
  protected function allowAffiliationForGroupTypes(array $bundles): void {
    $this->groupAffiliation
      ->method('getAffiliationEnabledGroupTypes')
      ->willReturn(array_fill_keys($bundles, $this->createMock(GroupTypeInterface::class)));
  }

  /**
   * Creates a profile mock.
   *
   * @param array $affiliation
   *   What ::getPrimaryAffiliation() returns.
   * @param array $fields
   *   Field item lists keyed by field name.
   * @param string[] $cache_tags
   *   Cache tags of the profile itself.
   *
   * @return \Drupal\social_profile\Entity\ProfileAffiliationInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The profile mock.
   */
  protected function createProfile(array $affiliation, array $fields = [], array $cache_tags = []): ProfileAffiliationInterface&MockObject {
    $profile = $this->createMock(ProfileAffiliationInterface::class);
    $profile->method('getPrimaryAffiliation')->willReturn($affiliation);
    // Mirrors SocialProfile::getPrimaryAffiliationType(), which reads the same
    // key off the same array.
    $profile->method('getPrimaryAffiliationType')->willReturn($affiliation['affiliation_type'] ?? '');
    $profile->method('getCacheContexts')->willReturn([]);
    $profile->method('getCacheTags')->willReturn($cache_tags);
    $profile->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);
    $profile->method('get')->willReturnCallback(
      fn (string $field_name) => $fields[$field_name] ?? $this->createFieldItemList(AccessResult::forbidden()),
    );

    return $profile;
  }

  /**
   * Creates a field item list mock.
   *
   * @param \Drupal\Core\Access\AccessResult $access
   *   The access result the field returns for the view operation.
   * @param \Drupal\group\Entity\GroupInterface|null $entity
   *   (optional) The referenced entity.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface>&\PHPUnit\Framework\MockObject\MockObject
   *   The field item list mock.
   */
  protected function createFieldItemList(AccessResult $access, ?GroupInterface $entity = NULL): FieldItemListInterface&MockObject {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('access')->with('view', NULL, TRUE)->willReturn($access);
    $field->method('__get')->with('entity')->willReturn($entity);

    return $field;
  }

  /**
   * Creates a group mock.
   *
   * @param string $bundle
   *   The group type id.
   * @param string[] $cache_tags
   *   Cache tags of the group.
   * @param string[] $cache_contexts
   *   Cache contexts of the group.
   * @param \Drupal\Core\Access\AccessResult|null $access
   *   (optional) The access result the group returns for the view operation.
   *
   * @return \Drupal\group\Entity\GroupInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The group mock.
   */
  protected function createGroup(string $bundle, array $cache_tags = [], array $cache_contexts = [], ?AccessResult $access = NULL): GroupInterface&MockObject {
    $group_type = $this->createMock(GroupTypeInterface::class);
    $group_type->method('getCacheContexts')->willReturn([]);
    $group_type->method('getCacheTags')->willReturn([]);
    $group_type->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);

    $group = $this->createMock(GroupInterface::class);
    $group->method('bundle')->willReturn($bundle);
    $group->method('getGroupType')->willReturn($group_type);
    $group->method('access')->with('view', NULL, TRUE)->willReturn($access ?? AccessResult::allowed());
    $group->method('getCacheContexts')->willReturn($cache_contexts);
    $group->method('getCacheTags')->willReturn($cache_tags);
    $group->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);

    return $group;
  }

}
