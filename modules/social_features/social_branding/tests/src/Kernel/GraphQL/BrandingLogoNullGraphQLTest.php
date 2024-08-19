<?php

namespace Drupal\Tests\social_branding\Kernel\GraphQL;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;

/**
 * Base class for branding related GraphQL tests.
 */
class BrandingLogoNullGraphQLTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    "social_branding",
    "social_branding_test",
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
   * Test that the platform branding logo url can return null.
   */
  public function testLogoUrlCanReturnNull(): void {
    $system_theme = $this->config('system.theme');
    // Set anonymous user.
    $this->setUpCurrentUser();

    $this->assertResults(
      '
        query {
          branding {
            logoUrl
          }
        }
      ',
      [],
      [
        'branding' => [
          'logoUrl' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($system_theme)
    );
  }

}
