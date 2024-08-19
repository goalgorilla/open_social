<?php

namespace Drupal\Tests\social_branding\Kernel\GraphQL;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;

/**
 * Base class for branding related GraphQL tests.
 */
class BrandingPreferredFeaturesEmptyGraphQLTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    "social_branding",
    "social_branding_empty_test",
    "jquery_ui",
    "jquery_ui_draggable",
    "jquery_ui_resizable",
  ];

  /**
   * {@inheritdoc}
   */
  public static $configSchemaCheckerExclusions = [
    'bootstrap.settings',
    // Delete it from exclusions when the schema is added.
    // @see https://www.drupal.org/project/socialbase/issues/3221046
    // @see https://www.drupal.org/project/socialblue/issues/3221047
    'socialbase.settings',
    'socialblue.settings',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // Use the socialblue theme.
    $this->container
      ->get('theme_installer')
      ->install([
        'socialblue',
      ]);
    $this->container
      ->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'socialblue')
      ->save();
    $this->container
      ->get('config.factory')
      ->getEditable('system.site')
      ->set('name', 'Open Social')
      ->save();
  }

  /**
   * Test that the preferred features can return an empty array.
   */
  public function testPreferredFeaturesReturnEmptyArray(): void {
    // Set anonymous user.
    $this->setUpCurrentUser();

    $this->assertResults(
      '
        query {
          preferredFeatures {
            machineName
          }
        }
      ',
      [],
      [
        'preferredFeatures' => [],
      ],
      $this->defaultCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
