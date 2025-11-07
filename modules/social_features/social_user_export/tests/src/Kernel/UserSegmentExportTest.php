<?php

declare(strict_types=1);

namespace Drupal\Tests\social_user_export\Kernel;

use Drupal\file\FileInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user_segments\Entity\UserSegment;
use Drupal\Core\File\FileSystemInterface;

/**
 * Tests user segment CSV export functionality.
 *
 * @group social_user_export
 *
 * Test methods:
 * - Access Control Tests:
 *   - Tests entity operation access with segments without rules
 *     testEntityOperationAccess()
 *   - Tests route access with segments without rules
 *     testRouteAccess()
 * - File Download Tests:
 *   - Tests file download access (with/without permission, owner/other user)
 *     testFileDownloadAccess()
 * - Export Functionality Tests:
 *   - Tests export when segment has no users
 *     testExportSegmentWithNoUsers()
 *
 * Data providers:
 * - Provides test cases for entity operation access
 *   providerEntityOperationAccess()
 * - Provides test cases for route access
 *   providerRouteAccess()
 * - Provides test cases for file download access
 *   providerFileDownloadAccess()
 */
class UserSegmentExportTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'file',
    'field',
    'node',
    'user_segments',
    'social_user_export',
    'hux',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // Register the private stream wrapper for file operations.
    $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set the private file path for the stream wrapper.
    $site_path = $this->container->getParameter('site.path');
    assert(is_string($site_path));
    $this->setSetting('file_private_path', $site_path . '/private');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user_segment');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['system', 'user', 'user_segments', 'social_user_export']);

    // Create user 1 to avoid implicit permissions.
    $this->createUser();
  }

  /**
   * Tests entity operation access.
   *
   * @param array $user_permissions
   *   Array of permissions for the user.
   * @param bool $segment_status
   *   TRUE for an enabled segment, FALSE for disabled.
   * @param bool $expected_has_operation
   *   TRUE if export_csv should be in operations.
   *
   * @dataProvider providerEntityOperationAccess
   */
  public function testEntityOperationAccess(array $user_permissions, bool $segment_status, bool $expected_has_operation): void {
    $user = $this->createUser($user_permissions);
    $this->assertNotFalse($user);
    $segment = $this->createSegmentWithoutRules($segment_status);

    // Set the current user so the access check works correctly.
    $this->setCurrentUser($user);

    // Check that the export operation is available or not based on
    // expectations. Use entity list builder to get all operations (default
    // operations and hook implementations), which is how operations are
    // actually retrieved in Drupal.
    $list_builder = $this->container->get('entity_type.manager')->getListBuilder('user_segment');
    $operations = $list_builder->getOperations($segment);
    if ($expected_has_operation) {
      $this->assertArrayHasKey('export_csv', $operations);
      $this->assertEquals('Export CSV', $operations['export_csv']['title']);
    }
    else {
      $this->assertArrayNotHasKey('export_csv', $operations);
    }
  }

  /**
   * Tests route access.
   *
   * @param array $user_permissions
   *   Array of permissions.
   * @param bool $segment_status
   *   TRUE for an enabled segment, FALSE for disabled.
   * @param bool $expected_access_allowed
   *   TRUE if access should be allowed.
   *
   * @dataProvider providerRouteAccess
   */
  public function testRouteAccess(array $user_permissions, bool $segment_status, bool $expected_access_allowed): void {
    $user = $this->createUser($user_permissions);
    $this->assertNotFalse($user);
    $segment = $this->createSegmentWithoutRules($segment_status);

    // Check entity access directly (route uses entity access check).
    $access_result = $segment->access('export', $user, TRUE);
    if ($expected_access_allowed) {
      $this->assertTrue($access_result->isAllowed());
    }
    else {
      $this->assertTrue($access_result->isForbidden());
    }
  }

  /**
   * Tests file download access for segment-users export.
   *
   * @param array $file_owner_permissions
   *   Permissions for the file owner.
   * @param array $current_user_permissions
   *   Permissions for the current user checking access.
   * @param bool $is_file_owner
   *   TRUE if the current user is the file owner, FALSE otherwise.
   * @param bool $expected_allowed
   *   TRUE if access should be allowed, FALSE if denied.
   *
   * @dataProvider providerFileDownloadAccess
   */
  public function testFileDownloadAccess(array $file_owner_permissions, array $current_user_permissions, bool $is_file_owner, bool $expected_allowed): void {
    $file_owner = $this->createUser($file_owner_permissions);
    $this->assertNotFalse($file_owner);
    $file = $this->createSegmentUsersExportFileMock($file_owner);

    // Set the current user (either owner or other user).
    if ($is_file_owner) {
      $current_user = $file_owner;
    }
    else {
      $current_user = $this->createUser($current_user_permissions);
      $this->assertNotFalse($current_user);
    }
    $this->container->get('current_user')->setAccount($current_user);

    // Check file download access.
    $file_uri = $file->getFileUri();
    $this->assertNotNull($file_uri);
    $result = social_user_export_file_download($file_uri);
    if ($expected_allowed) {
      $this->assertIsArray($result);
      $this->assertArrayHasKey('Content-disposition', $result);
    }
    else {
      $this->assertEquals(-1, $result);
    }
  }

  /**
   * Tests export when segment has no users.
   */
  public function testExportSegmentWithNoUsers(): void {
    // Segment without rules cannot be queried for users, so pass an empty array
    // directly.
    $user_ids = [];

    // Try to export - should return NULL when no users are provided.
    $export_service = $this->container->get('social_user_export.user_export');
    $result = $export_service->exportUsers($user_ids, NULL, 'segment-users');

    $this->assertNull($result);
  }

  /**
   * Data provider for entity operation access tests.
   *
   * @return array
   *   Array of test cases with:
   *   - user_permissions: array of permissions for the user
   *   - segment_status: TRUE for enabled, FALSE for disabled
   *   - expected_has_operation: TRUE if export_csv should be in operations
   */
  public function providerEntityOperationAccess(): array {
    return [
      'user with permission, enabled segment' => [
        ['administer user_segment'],
        TRUE,
        TRUE,
      ],
      'user with permission, disabled segment' => [
        ['administer user_segment'],
        FALSE,
        FALSE,
      ],
      'user without permission, enabled segment' => [
        [],
        TRUE,
        FALSE,
      ],
      'user without permission, disabled segment' => [
        [],
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Data provider for route access tests.
   *
   * @return array
   *   Array of test cases with:
   *   - user_permissions: array of permissions
   *   - segment_status: TRUE for enabled, FALSE for disabled
   *   - expected_access_allowed: TRUE if access should be allowed
   */
  public function providerRouteAccess(): array {
    return [
      'user with permission, enabled segment' => [
        ['administer user_segment'],
        TRUE,
        TRUE,
      ],
      'user with permission, disabled segment' => [
        ['administer user_segment'],
        FALSE,
        FALSE,
      ],
      'user without permission, enabled segment' => [
        [],
        TRUE,
        FALSE,
      ],
      'user without permission, disabled segment' => [
        [],
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Data provider for file download access tests.
   *
   * @return array
   *   Array of test cases with:
   *   - file_owner_permissions: array of permissions for file owner
   *   - current_user_permissions: array of permissions for current user
   *   - is_file_owner: TRUE if current user is a file owner, FALSE otherwise
   *   - expected_allowed: TRUE if access should be allowed, FALSE if denied
   */
  public function providerFileDownloadAccess(): array {
    return [
      'user with permission (also owner)' => [
        ['administer user_segment'],
        ['administer user_segment'],
        TRUE,
        TRUE,
      ],
      'user without permission (not owner)' => [
        ['administer user_segment'],
        [],
        FALSE,
        FALSE,
      ],
      'file owner without permission' => [
        [],
        [],
        TRUE,
        TRUE,
      ],
      'other user without permission (not owner)' => [
        [],
        [],
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Creates a user segment without rules.
   *
   * Creates a segment without defining the rules field. This represents a
   * segment where rules are not set.
   *
   * @param bool $enabled
   *   TRUE for an enabled segment, FALSE for disabled.
   * @param string|null $label
   *   Optional label for the segment.
   *
   * @return \Drupal\user_segments\Entity\UserSegment
   *   The created segment.
   */
  protected function createSegmentWithoutRules(bool $enabled = TRUE, ?string $label = NULL): UserSegment {
    $segment = UserSegment::create([
      'label' => $label ?? ($enabled ? 'Test Segment Without Rules' : 'Test Segment Without Rules Disabled'),
      'status' => $enabled,
    ]);
    $segment->save();
    return $segment;
  }

  /**
   * Creates a mock segment-users export file for testing.
   *
   * @param \Drupal\Core\Session\AccountInterface $owner
   *   The file owner.
   *
   * @return \Drupal\file\FileInterface
   *   The created file.
   */
  protected function createSegmentUsersExportFileMock(AccountInterface $owner): FileInterface {
    $name = 'export-segment-users-' . bin2hex(random_bytes(8)) . '.csv';
    $path = 'private://csv';
    $this->container->get('file_system')->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $content = "Header1,Header2,Header3\nValue1,Value2,Value3\n";
    $file = $this->container->get('file.repository')->writeData($content, $path . '/' . $name);
    $file->setOwnerId($owner->id());
    $file->save();

    return $file;
  }

}
