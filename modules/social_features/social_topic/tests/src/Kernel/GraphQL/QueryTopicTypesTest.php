<?php

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;

/**
 * Tests the topicTypes field on the Query type.
 *
 * @group social_graphql
 * @group social_topic
 */
class QueryTopicTypesTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'social_topic',
    'entity',
    // For the topic author and viewer.
    'social_user',
    'user',
    // User creation in social_user requires a service in role_delegation.
    "role_delegation",
    // social_comment configures topics for nodes.
    'node',
    // The default topic config contains a body text field.
    'field',
    'text',
    'filter',
    'file',
    'image',
    // For the comment functionality.
    'social_comment',
    'comment',
    // node.type.topic has a configuration dependency on the menu_ui module.
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
      'social_topic',
    ]);
  }

  /**
   * Test that a anonymous user can access topic types.
   */
  public function testAnonymousUserCanAccessTopicTypes() {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
      'vid' => 'topic_types',
      'name' => $this->randomString(),
    ]);

    $term->save();

    $this->setUpCurrentUser([], ['access content']);

    $this->assertResults('
          query {
            topicTypes {
              id
              label
            }
          }
        ',
      [],
      [
        'topicTypes' => [
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

}
