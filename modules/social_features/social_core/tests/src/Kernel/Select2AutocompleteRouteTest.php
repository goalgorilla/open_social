<?php

namespace Drupal\Tests\social_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confirm that permission override for select2 route work.
 *
 * @group social_core
 */
class Select2AutocompleteRouteTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'language',
    'social_core',
    'social_core_test',
    'system',
    'field',
    'select2',
    'social_user',
    "role_delegation",
    "profile",
    // Required for third party config schema.
    "field_group",
    // Modules needed for profile fields.
    "file",
    "image",
    "address",
    "taxonomy",
    "telephone",
    "text",
    "options",
    "filter",
    "lazy",
    "image_widget_crop",
    "crop",
    "social_profile",
    "group",
    "social_group",
    "flexible_permissions",
    "flag",
    "paragraphs",
    "entity_reference_revisions",
    "views_bulk_operations",
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // @todo when https://www.drupal.org/project/social/issues/3238713 is fixed.
    "core.entity_form_display.profile.profile.default",
    "views.view.newest_users",
    "views.view.user_information",
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('profile_type');
    $this->installEntitySchema('profile');
    $this->installConfig('social_profile');
    $this->installConfig(['social_core_test']);
  }

  /**
   * Generates a valid selection settings key and registers it in keyvalue.
   *
   * @param string $target_type
   *   The target entity type.
   * @param string $selection_handler
   *   The selection handler ID.
   * @param array $settings
   *   The selection handler settings.
   *
   * @return string
   *   The hashed selection settings key.
   */
  protected function registerSelectionSettings(string $target_type, string $selection_handler, array $settings = []): string {
    $hash_salt = Settings::getHashSalt();
    $selection_key = Crypt::hmacBase64(
      serialize($settings) . $target_type . $selection_handler,
      $hash_salt
    );

    $this->container
      ->get('keyvalue')
      ->get('entity_autocomplete')
      ->set($selection_key, $settings);

    return $selection_key;
  }

  /**
   * Builds and dispatches a request to the autocomplete route.
   *
   * @param string $selection_key
   *   The hashed selection key to include in the route.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response from the kernel.
   */
  protected function dispatchAutocompleteRequest(string $selection_key): Response {
    $request = Request::create("select2_autocomplete/user/social/{$selection_key}?term=test&_type=query&q=test");
    return $this->container->get('http_kernel')->handle($request);
  }

  /**
   * Tests access denial for a user without the required permission.
   */
  public function testCheckAccessForNoPermUser(): void {
    $selection_key = $this->registerSelectionSettings('user', 'social');
    $response = $this->dispatchAutocompleteRequest($selection_key);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * Tests access is granted for an authenticated user with correct permission.
   */
  public function testCheckAccessForAuthenticatedUser(): void {
    $this->setUpCurrentUser(['uid' => 1], ['use select2 autocomplete'], TRUE);
    $selection_key = $this->registerSelectionSettings('user', 'social');
    $response = $this->dispatchAutocompleteRequest($selection_key);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

}
