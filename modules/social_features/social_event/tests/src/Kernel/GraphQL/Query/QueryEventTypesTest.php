<?php

namespace Drupal\Tests\social_event\Kernel\GraphQL\Query;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;

/**
 * Tests the eventTypes field on the Query type.
 *
 * @group social_graphql
 * @group social_event
 */
class QueryEventTypesTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'social_event',
    'social_event_type',
    'entity',
    // For the event author and viewer.
    'social_user',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // social_comment configures events for nodes.
    'node',
    // The default event config contains a body text field.
    'field',
    'text',
    'filter',
    'file',
    'image',
    // For the comment functionality.
    'social_comment',
    'comment',
    'menu_ui',
    'entity_access_by_field',
    'options',
    'taxonomy',
    'path',
    'image_widget_crop',
    'crop',
    'field_group',
    'social_node',
    'social_core',
    'block',
    'block_content',
    'image_effects',
    'file_mdm',
    'group_core_comments',
    'views',
    'group',
    'datetime',

    // Meeting API modules required by social_event configurations.
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',

    'address',
    'profile',
    'social_profile',
    'variationcache',
    'social_tagging',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    $this->installConfig([
      'node',
      'social_core',
      'social_node',
      'social_event',
      'social_event_type',
      'social_tagging',
    ]);
  }

  /**
   * Test that event types are accessible via GraphQL API based on permissions.
   *
   * Tests that the eventTypes query respects Drupal access control. By default,
   * this query is publicly accessible (requires 'access content' permission,
   * which is typically granted to anonymous users), allowing API consumers to
   * query the list of available event types. This validates that the query
   * correctly implements permission-based access control.
   */
  public function testEventTypesAccessibleWithAccessContentPermission(): void {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => $this->randomString(),
    ]);

    $term->save();

    $this->setUpCurrentUser([], ['access content']);

    $this->assertResults('
          query {
            eventTypes {
              id
              label
            }
          }
        ',
      [],
      [
        'eventTypes' => [
          [
            'id' => $term->uuid(),
            'label' => $term->label(),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($term)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that multiple event types are returned correctly via GraphQL API.
   *
   * Tests that the eventTypes query correctly returns multiple event types
   * when they exist. This validates that the query handles multiple results
   * and returns them in the expected order (sorted by taxonomy term weight
   * and name).
   */
  public function testMultipleEventTypesAreReturned(): void {
    $term1 = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Workshop',
    ]);
    $term1->save();

    $term2 = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Conference',
    ]);
    $term2->save();

    $term3 = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Webinar',
    ]);
    $term3->save();

    $this->setUpCurrentUser([], ['access content']);

    // Note: taxonomy_load_tree returns terms sorted by weight, then name.
    // Since weights are not set (they default to 0), all terms have equal
    // weight, so sorting falls back to alphabetical by name. We sort expected
    // results by label to match the actual order.
    $expected_terms = [
      [
        'id' => $term2->uuid(),
        'label' => $term2->label(),
      ],
      [
        'id' => $term1->uuid(),
        'label' => $term1->label(),
      ],
      [
        'id' => $term3->uuid(),
        'label' => $term3->label(),
      ],
    ];
    // Sort by label alphabetically (Conference, Webinar, Workshop).
    usort($expected_terms, function ($a, $b) {
      return strcmp((string) $a['label'], (string) $b['label']);
    });

    $this->assertResults('
          query {
            eventTypes {
              id
              label
            }
          }
        ',
      [],
      [
        'eventTypes' => $expected_terms,
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($term1)
        ->addCacheableDependency($term2)
        ->addCacheableDependency($term3)
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that empty array is returned when no event types exist.
   *
   * Tests that the eventTypes query correctly returns an empty array when
   * no event types are available on the platform. This validates that the
   * query handles the empty state gracefully without errors.
   */
  public function testEmptyEventTypesReturnsEmptyArray(): void {
    $this->setUpCurrentUser([], ['access content']);

    $this->assertResults('
          query {
            eventTypes {
              id
              label
            }
          }
        ',
      [],
      [
        'eventTypes' => [],
      ],
      $this->defaultCacheMetaData()
        ->addCacheTags(['taxonomy_term_list'])
        ->addCacheContexts(['languages:language_interface'])
    );
  }

  /**
   * Test that event types query respects access content permission.
   *
   * Tests that the eventTypes query correctly filters results based on
   * 'access content' permission. API consumers without this permission
   * receive an empty array, ensuring that access control is properly
   * enforced at the API level.
   */
  public function testEventTypesNotAccessibleWithoutAccessContentPermission(): void {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => $this->randomString(),
    ]);
    $term->save();

    // Set up context without 'access content' permission.
    $this->setUpCurrentUser();

    $this->assertResults('
          query {
            eventTypes {
              id
              label
            }
          }
        ',
      [],
      [
        'eventTypes' => [],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($term)
        ->addCacheContexts(['languages:language_interface', 'user.permissions'])
    );
  }

  /**
   * Test that unpublished event types are filtered based on permissions.
   *
   * Tests that the eventTypes query correctly filters out unpublished
   * taxonomy terms for API consumers without 'administer taxonomy' permission.
   * This ensures that draft/unpublished event types are not exposed through
   * the GraphQL API to regular consumers, protecting unpublished content.
   */
  public function testUnpublishedEventTypesFilteredWithoutAdministerTaxonomyPermission(): void {
    $published_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Published Type',
      'status' => 1,
    ]);
    $published_term->save();

    $unpublished_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Unpublished Type',
      'status' => 0,
    ]);
    $unpublished_term->save();

    // Set up context with 'access content' but without 'administer taxonomy'.
    $this->setUpCurrentUser([], ['access content']);

    $this->assertResults('
          query {
            eventTypes {
              id
              label
            }
          }
        ',
      [],
      [
        'eventTypes' => [
          [
            'id' => $published_term->uuid(),
            'label' => $published_term->label(),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($published_term)
        ->addCacheableDependency($unpublished_term)
        ->addCacheContexts(['languages:language_interface', 'user.permissions'])
    );
  }

  /**
   * Test that unpublished event types are visible with correct permissions.
   *
   * Tests that the eventTypes query correctly includes unpublished taxonomy
   * terms for API consumers with 'administer taxonomy' permission. This
   * validates that the access control mechanism properly allows administrative
   * access to draft/unpublished event types through the GraphQL API.
   */
  public function testAllEventTypesVisibleWithAdministerTaxonomyPermission(): void {
    $published_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Published Type',
      'status' => 1,
    ]);
    $published_term->save();

    $unpublished_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'event_types',
      'name' => 'Unpublished Type',
      'status' => 0,
    ]);
    $unpublished_term->save();

    // Set up context with 'administer taxonomy' and 'access content'
    // permissions.
    $this->setUpCurrentUser([], ['administer taxonomy', 'access content']);

    $this->assertResults('
          query {
            eventTypes {
              id
              label
            }
          }
        ',
      [],
      [
        'eventTypes' => [
          [
            'id' => $published_term->uuid(),
            'label' => $published_term->label(),
          ],
          [
            'id' => $unpublished_term->uuid(),
            'label' => $unpublished_term->label(),
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($published_term)
        ->addCacheableDependency($unpublished_term)
        ->addCacheContexts(['languages:language_interface', 'user.permissions'])
    );
  }

}
