<?php

declare(strict_types=1);

namespace Drupal\Tests\social_course\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the "Course type" vocabulary permissions per role.
 *
 * @group social_course
 */
class SocialCourseTypePermissionsTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'group',
    'options',
    'entity',
    'node',
    'gnode',
    'social_group',
    'flag',
    'address',
    'image',
    'file',
    'entity_access_by_field',
    'flexible_permissions',
    'taxonomy',
    'taxonomy_access_fix',
    'social_course',
  ];

  /**
   * The roles that receive course permissions.
   */
  private const ROLES = [
    RoleInterface::ANONYMOUS_ID,
    RoleInterface::AUTHENTICATED_ID,
    'verified',
    'contentmanager',
    'sitemanager',
  ];

  /**
   * Permissions that allow managing course types.
   */
  private const MANAGE_PERMISSIONS = [
    'create terms in course_type',
    'edit terms in course_type',
    'delete terms in course_type',
    'reorder terms in course_type',
  ];

  /**
   * Permissions that allow seeing course type labels.
   */
  private const VIEW_PERMISSIONS = [
    'view terms in course_type',
    'view term names in course_type',
  ];

  /**
   * The "Course type" vocabulary.
   */
  protected VocabularyInterface $vocabulary;

  /**
   * A course type term.
   */
  protected TermInterface $term;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    $this->vocabulary = Vocabulary::create([
      'vid' => 'course_type',
      'name' => 'Course type',
    ]);
    $this->vocabulary->save();

    $this->term = Term::create([
      'vid' => 'course_type',
      'name' => 'Workshop',
    ]);
    $this->term->save();

    foreach (self::ROLES as $role_id) {
      if (Role::load($role_id) === NULL) {
        Role::create(['id' => $role_id, 'label' => $role_id])->save();
      }
    }

    // Social Core grants site managers access to the taxonomy overview.
    user_role_grant_permissions('sitemanager', ['access taxonomy overview']);

    $this->container->get('module_handler')->loadInclude('social_course', 'install');
  }

  /**
   * Tests the permissions new platforms get when installing the module.
   */
  public function testInstallPermissions(): void {
    _social_course_set_permissions();

    $this->assertCourseTypePermissions();
  }

  /**
   * Tests the update hook grants the permissions on existing platforms.
   */
  public function testUpdateHookPermissions(): void {
    social_course_update_26000();

    $this->assertCourseTypePermissions();

    // The update grants only course type permissions, so it does not re-grant
    // other course permissions that a platform may have revoked.
    $this->assertFalse($this->loadRole(RoleInterface::ANONYMOUS_ID)->hasPermission('access course overview'));
    $this->assertFalse($this->loadRole('contentmanager')->hasPermission('create course_article content'));
  }

  /**
   * Tests access to course types for each role.
   */
  public function testCourseTypeAccess(): void {
    social_course_update_26000();

    $site_manager = $this->createUser(values: ['roles' => ['sitemanager']]);
    $content_manager = $this->createUser(values: ['roles' => ['contentmanager']]);
    $authenticated = $this->createUser();
    $anonymous = new AnonymousUserSession();

    // Site managers see the vocabulary on the overview and manage terms.
    $this->assertTrue($this->vocabulary->access('view', $site_manager));
    $this->assertTrue($this->createTermAccess($site_manager));
    $this->assertTrue($this->term->access('update', $site_manager));
    $this->assertTrue($this->term->access('delete', $site_manager));
    $this->assertTrue($this->vocabulary->access('reorder_terms', $site_manager));

    // Content managers can't manage course types.
    $this->assertFalse($this->createTermAccess($content_manager));
    $this->assertFalse($this->term->access('update', $content_manager));
    $this->assertFalse($this->term->access('delete', $content_manager));

    // Every role sees the course type label.
    foreach ([$site_manager, $content_manager, $authenticated, $anonymous] as $account) {
      $this->assertTrue($this->term->access('view', $account));
      $this->assertTrue($this->term->access('view label', $account));
    }
  }

  /**
   * Asserts each role has the expected course type permissions.
   */
  protected function assertCourseTypePermissions(): void {
    foreach (self::ROLES as $role_id) {
      $role = $this->loadRole($role_id);

      foreach (self::VIEW_PERMISSIONS as $permission) {
        $this->assertTrue($role->hasPermission($permission), "Role $role_id has '$permission'.");
      }

      $can_manage = $role_id === 'sitemanager';
      foreach (self::MANAGE_PERMISSIONS as $permission) {
        $this->assertSame($can_manage, $role->hasPermission($permission), "Role $role_id has '$permission': " . var_export($can_manage, TRUE));
      }

      $can_select = in_array($role_id, ['contentmanager', 'sitemanager'], TRUE);
      $this->assertSame($can_select, $role->hasPermission('select terms in course_type'), "Role $role_id can select course types: " . var_export($can_select, TRUE));
    }
  }

  /**
   * Checks whether the account can create course type terms.
   */
  protected function createTermAccess(AccountInterface $account): bool {
    return $this->entityTypeManager
      ->getAccessControlHandler('taxonomy_term')
      ->createAccess('course_type', $account);
  }

  /**
   * Loads a role, bypassing the static cache.
   */
  protected function loadRole(string $role_id): RoleInterface {
    $role = $this->entityTypeManager->getStorage('user_role')->loadUnchanged($role_id);
    $this->assertInstanceOf(RoleInterface::class, $role);
    return $role;
  }

}
