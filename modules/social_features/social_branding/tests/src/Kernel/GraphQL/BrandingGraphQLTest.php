<?php

namespace Drupal\Tests\social_branding\Kernel\GraphQL;

use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\social_branding\Wrappers\Color;

/**
 * Base class for branding related GraphQL tests.
 */
class BrandingGraphQLTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    "social_branding",
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
  }

  /**
   * Ensure the platform branding fields are properly added to the endpoint.
   */
  public function testPlatformBrandingFieldsPresence() : void {
    $system_theme = $this->config('system.theme');
    $config = $this->config('socialblue.settings');
    // Prepare logo url.
    $config->set('logo.path', 'public://logo.png')->save();
    $expected_logo_url = 'http://localhost/' . $this->siteDirectory . '/files/logo.png';
    // Set anonymous user.
    $this->setUpCurrentUser();

    $this->assertResults(
      '
        query {
          platformBranding {
            logoUrl
            brandingColors {
              primary {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              secondary {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              accentBackground {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              accentText {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              link {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              navbarBackground {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              navbarText {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              navbarActiveBackground {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              navbarActiveText {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              navbarSecondaryBackground {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
              navbarSecondaryText {
                css
                hexRGB
                rgba {
                  red
                  green
                  blue
                  alpha
                }
              }
            }
          }
        }
      ',
      [],
      [
        'platformBranding' => [
          'logoUrl' => $expected_logo_url,
          'brandingColors' => [
            'primary' => [
              'css' => $this->getColor($config->get('color_primary'))->css(),
              'hexRGB' => $this->getColor($config->get('color_primary'))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get('color_primary'))->red(),
                'green' => $this->getColor($config->get('color_primary'))->green(),
                'blue' => $this->getColor($config->get('color_primary'))->blue(),
                'alpha' => $this->getColor($config->get('color_primary'))->alpha(),
              ],
            ],
            'secondary' => [
              'css' => $this->getColor($config->get('color_secondary'))->css(),
              'hexRGB' => $this->getColor($config->get('color_secondary'))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get('color_secondary'))->red(),
                'green' => $this->getColor($config->get('color_secondary'))->green(),
                'blue' => $this->getColor($config->get('color_secondary'))->blue(),
                'alpha' => $this->getColor($config->get('color_secondary'))->alpha(),
              ],
            ],
            'accentBackground' => [
              'css' => $this->getColor($config->get('color_accent'))->css(),
              'hexRGB' => $this->getColor($config->get('color_accent'))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get('color_accent'))->red(),
                'green' => $this->getColor($config->get('color_accent'))->green(),
                'blue' => $this->getColor($config->get('color_accent'))->blue(),
                'alpha' => $this->getColor($config->get('color_accent'))->alpha(),
              ],
            ],
            'accentText' => [
              'css' => $this->getColor($config->get('color_accent_text'))->css(),
              'hexRGB' => $this->getColor($config->get('color_accent_text'))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get('color_accent_text'))->red(),
                'green' => $this->getColor($config->get('color_accent_text'))->green(),
                'blue' => $this->getColor($config->get('color_accent_text'))->blue(),
                'alpha' => $this->getColor($config->get('color_accent_text'))->alpha(),
              ],
            ],
            'link' => [
              'css' => $this->getColor($config->get('color_link'))->css(),
              'hexRGB' => $this->getColor($config->get('color_link'))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get('color_link'))->red(),
                'green' => $this->getColor($config->get('color_link'))->green(),
                'blue' => $this->getColor($config->get('color_link'))->blue(),
                'alpha' => $this->getColor($config->get('color_link'))->alpha(),
              ],
            ],
            'navbarBackground' => [
              'css' => $this->getColor($config->get('color_navbar_bg'))->css(),
              'hexRGB' => $this->getColor($config->get('color_navbar_bg'))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get('color_navbar_bg'))->red(),
                'green' => $this->getColor($config->get('color_navbar_bg'))->green(),
                'blue' => $this->getColor($config->get('color_navbar_bg'))->blue(),
                'alpha' => $this->getColor($config->get('color_navbar_bg'))->alpha(),
              ],
            ],
            'navbarText' => [
              'css' => $this->getColor($config->get('color_navbar_text'))->css(),
              'hexRGB' => $this->getColor($config->get('color_navbar_text'))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get('color_navbar_text'))->red(),
                'green' => $this->getColor($config->get('color_navbar_text'))->green(),
                'blue' => $this->getColor($config->get('color_navbar_text'))->blue(),
                'alpha' => $this->getColor($config->get('color_navbar_text'))->alpha(),
              ],
            ],
            'navbarActiveBackground' => [
              'css' => $this->getColor($config->get("color_navbar_active_bg'"))->css(),
              'hexRGB' => $this->getColor($config->get("color_navbar_active_bg'"))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get("color_navbar_active_bg'"))->red(),
                'green' => $this->getColor($config->get("color_navbar_active_bg'"))->green(),
                'blue' => $this->getColor($config->get("color_navbar_active_bg'"))->blue(),
                'alpha' => $this->getColor($config->get("color_navbar_active_bg'"))->alpha(),
              ],
            ],
            'navbarActiveText' => [
              'css' => $this->getColor($config->get("color_navbar_active_text'"))->css(),
              'hexRGB' => $this->getColor($config->get("color_navbar_active_text'"))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get("color_navbar_active_text'"))->red(),
                'green' => $this->getColor($config->get("color_navbar_active_text'"))->green(),
                'blue' => $this->getColor($config->get("color_navbar_active_text'"))->blue(),
                'alpha' => $this->getColor($config->get("color_navbar_active_text'"))->alpha(),
              ],
            ],
            'navbarSecondaryBackground' => [
              'css' => $this->getColor($config->get("color_navbar_sec_bg'"))->css(),
              'hexRGB' => $this->getColor($config->get("color_navbar_sec_bg'"))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get("color_navbar_sec_bg'"))->red(),
                'green' => $this->getColor($config->get("color_navbar_sec_bg'"))->green(),
                'blue' => $this->getColor($config->get("color_navbar_sec_bg'"))->blue(),
                'alpha' => $this->getColor($config->get("color_navbar_sec_bg'"))->alpha(),
              ],
            ],
            'navbarSecondaryText' => [
              'css' => $this->getColor($config->get("color_navbar_sec_text'"))->css(),
              'hexRGB' => $this->getColor($config->get("color_navbar_sec_text'"))->hexRgb(),
              'rgba' => [
                'red' => $this->getColor($config->get("color_navbar_sec_text'"))->red(),
                'green' => $this->getColor($config->get("color_navbar_sec_text'"))->green(),
                'blue' => $this->getColor($config->get("color_navbar_sec_text'"))->blue(),
                'alpha' => $this->getColor($config->get("color_navbar_sec_text'"))->alpha(),
              ],
            ],
          ],
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($system_theme)
        ->addCacheableDependency($config)
    );
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
          platformBranding {
            logoUrl
          }
        }
      ',
      [],
      [
        'platformBranding' => [
          'logoUrl' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($system_theme)
    );
  }

  /**
   * Test that the platform branding colors can return null.
   */
  public function testBrandingColorsCanReturnNull(): void {
    $system_theme = $this->config('system.theme');
    // Change default theme.
    $system_theme->set('default', 'bootstrap')->save();
    // Set anonymous user.
    $this->setUpCurrentUser();

    $this->assertResults(
      '
        query {
          platformBranding {
            brandingColors {
              primary {
                hexRGB
                css
                rgba {
                  alpha
                  blue
                  green
                  red
                }
              }
            }
          }
        }
      ',
      [],
      [
        'platformBranding' => [
          'brandingColors' => NULL,
        ],
      ],
      $this->defaultCacheMetaData()
        ->addCacheableDependency($system_theme)
    );
  }

  /**
   * Get Color instance.
   *
   * @param string $color
   *   The color as a hexadecimal RGB string: e.g. #FF33AA.
   */
  private function getColor(string $color): Color {
    return new Color($color);
  }

}
