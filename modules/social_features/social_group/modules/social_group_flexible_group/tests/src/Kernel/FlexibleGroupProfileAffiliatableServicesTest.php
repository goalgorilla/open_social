<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests flexible-group affiliation mapper container tags.
 *
 * @group social_group_flexible_group
 */
class FlexibleGroupProfileAffiliatableServicesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'flexible_permissions',
    'group',
    'social_group_flexible_group',
    'social_group',
    'social_core',
    'dynamic_entity_reference',
    'message',
    'state_machine',
    'grequest',
    'social_group_request',
    'social_tagging',
    'ginvite',
    'better_exposed_filters',
    'views_bulk_operations',
    'flag',
    'gnode',
    'social_event',
    'social_topic',
    'image',
    'file',
    'smart_trim',
    'node',
    'profile',
    'social_profile',
    'views',
    'group_core_comments',
    'menu_ui',
    'comment',
    'social_node',
    'datetime',
    'select2',
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',
    'inline_entity_form',
    'telephone',
    'paragraphs',
    'entity_reference_revisions',
    'address',
    'field_group',
    'file_mdm',
    'image_effects',
    'image_widget_crop',
    'crop',
    'block',
    'block_content',
    'entity_access_by_field',
    'link',
    'activity_logger',
    'activity_creator',
    'lazy',
    'entity_browser',
    'media',
    'taxonomy',
  ];

  /**
   * Flexible-group mappers are collected from tagged iterators.
   */
  public function testFlexibleGroupMappersAreTaggedInContainer(): void {
    $affiliatable = $this->container->findTaggedServiceIds(
      'social_profile.profile_affiliatable_type',
    );
    $this->assertArrayHasKey(
      'social_group_flexible_group.profile_affiliatable_type_mapper',
      $affiliatable,
    );

    $hubs = $this->container->findTaggedServiceIds('social_group.hub_type');
    $this->assertArrayHasKey(
      'social_group_flexible_group.hub_type_mapper',
      $hubs,
    );
  }

}
