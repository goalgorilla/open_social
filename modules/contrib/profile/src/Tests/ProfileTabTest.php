<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileTabTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\user\Entity\User;
use Drupal\system\Tests\Menu\LocalTasksTest;

/**
 * Tests tab functionality of profiles.
 *
 * @group profile
 */
class ProfileTabTest extends ProfileTestBase {

  public static $modules = ['profile', 'field_ui', 'text', 'block'];

  /**
   * Testing demo user 1.
   *
   * @var \Drupal\user\UserInterface
   */
  public $user1;

  /**
   * Testing demo user 2.
   *
   * @var \Drupal\user\UserInterface;
   */
  public $user2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access user profiles',
      'administer profiles',
      'administer profile types',
      'bypass profile access',
      'access administration pages',
    ]);
  }

  /**
   * Tests tabs in profile UI.
   */
  public function testProfileTabs() {
    $types_data = [
      'profile_type_0' => ['label' => $this->randomMachineName()],
      'profile_type_1' => ['label' => $this->randomMachineName()],
    ];

    /** @var ProfileType[] $types */
    $types = [];
    foreach ($types_data as $id => $values) {
      $types[$id] = ProfileType::create(['id' => $id] + $values);
      $types[$id]->save();
    }
    $this->container->get('router.builder')->rebuild();

    $this->user1 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user1->save();
    $this->user2 = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->user2->save();

    // Create new profiles.
    $profile1 = Profile::create($expected = [
      'type' => $types['profile_type_0']->id(),
      'uid' => $this->user1->id(),
    ]);
    $profile1->save();
    $profile2 = Profile::create($expected = [
      'type' => $types['profile_type_1']->id(),
      'uid' => $this->user2->id(),
    ]);
    $profile2->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config');
    $this->clickLink('User profiles');
    $this->assertResponse(200);
    $this->assertUrl('admin/config/people/profiles');

    $this->assertLink($profile1->label());
    $this->assertLinkByHref($profile2->toUrl('canonical')->toString());

    $tasks = [
      ['entity.profile.collection', []],
      ['entity.profile_type.collection', []],
    ];

    $this->assertLocalTasks($tasks, 0);
  }

  /**
   * Asserts local tasks in the page output.
   *
   * @param array $routes
   *   A list of expected local tasks, prepared as an array of route names and
   *   their associated route parameters, to assert on the page (in the given
   *   order).
   * @param int $level
   *   (optional) The local tasks level to assert; 0 for primary, 1 for
   *   secondary. Defaults to 0.
   */
  protected function assertLocalTasks(array $routes, $level = 0) {
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(
      ':class' => $level == 0 ? 'tabs primary' : 'tabs secondary',
    ));
    $this->assertTrue(count($elements), 'Local tasks found.');
    foreach ($routes as $index => $route_info) {
      list($route_name, $route_parameters) = $route_info;
      $expected = Url::fromRoute($route_name, $route_parameters)->toString();
      $method = ($elements[$index]['href'] == $expected ? 'pass' : 'fail');
      $this->{$method}(new FormattableMarkup('Task @number href @value equals @expected.', [
        '@number' => $index + 1,
        '@value' => (string) $elements[$index]['href'],
        '@expected' => $expected,
      ]));
    }
  }

}
