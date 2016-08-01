<?php

namespace Drupal\address\Tests;

use Drupal\address\Entity\Zone;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the zone entity and UI.
 *
 * @group address
 */
class ZoneTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'block',
    'system',
    'user',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->adminUser = $this->drupalCreateUser([
      'administer zones',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests creating a zone via UI.
   */
  function testCreateZone() {
    $this->drupalGet('admin/config/regional/zones/add');

    // Add a Country zone member, select the US.
    $zone_member_values = [
      'plugin' => 'country',
    ];
    $this->drupalPostForm(NULL, $zone_member_values, t('Add'));
    $country_values = [
      'members[0][form][country_code]' => 'US',
    ];
    $this->drupalPostAjaxForm(NULL, $country_values, 'members[0][form][country_code]');
    // Add an EU zone member.
    $zone_member_values = [
      'plugin' => 'eu',
    ];
    $this->drupalPostForm(NULL, $zone_member_values, t('Add'));
    // Add, then remove a Zone zone member.
    // Confirms that removing unsaved zone members works.
    $zone_member_values = [
      'plugin' => 'zone',
    ];
    $this->drupalPostForm(NULL, $zone_member_values, t('Add'));
    $this->drupalPostAjaxForm(NULL, [], 'remove_member2');

    // Finish creating the zone and zone members.
    $edit = [
      'id' => 'test_zone',
      'name' => 'Test zone',
      'scope' => $this->randomMachineName(6),
      'members[0][form][name]' => 'California',
      'members[0][form][country_code]' => 'US',
      'members[0][form][administrative_area]' => 'US-CA',
      'members[0][form][included_postal_codes]' => '123',
      'members[0][form][excluded_postal_codes]' => '456',
      'members[1][form][name]' => 'European Union',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $zone = Zone::load($edit['id']);
    $this->assertEqual($zone->getName(), $edit['name'], 'The created zone has the correct name.');
    $this->assertEqual($zone->getScope(), $edit['scope'], 'The created zone has the correct scope.');
    $members = $zone->getMembers();
    $this->assertTrue(count($members) == 2, 'The created zone has the correct number of members.');
    // $members is a plugin collection which doesn't support array access.
    $members_array = [];
    foreach ($members as $member) {
      $members_array[] = $member;
    }
    $first_member = reset($members_array);
    $this->assertEqual($first_member->getName(), $edit['members[0][form][name]'], 'The first created zone member has the correct name.');
    $first_member_configuration = $first_member->getConfiguration();
    $this->assertEqual($first_member_configuration['country_code'], $edit['members[0][form][country_code]'], 'The first created zone member has the correct country.');
    $this->assertEqual($first_member_configuration['administrative_area'], $edit['members[0][form][administrative_area]'], 'The first created zone member has the correct administrative area.');
    $this->assertEqual($first_member_configuration['included_postal_codes'], $edit['members[0][form][included_postal_codes]'], 'The first created zone member has the correct included postal codes.');
    $this->assertEqual($first_member_configuration['excluded_postal_codes'], $edit['members[0][form][excluded_postal_codes]'], 'The first created zone member has the correct excluded postal codes.');
    $second_member = end($members_array);
    $this->assertEqual($second_member->getName(), $edit['members[1][form][name]'], 'The second created zone member has the correct name.');

    // Add another zone that references the current one.
    $this->drupalGet('admin/config/regional/zones/add');
    $zone_member_values = [
      'plugin' => 'zone',
    ];
    $this->drupalPostForm(NULL, $zone_member_values, t('Add'));

    $edit = [
      'id' => 'test_zone2',
      'name' => $this->randomMachineName(),
      'members[0][form][name]' => 'Previous zone',
      'members[0][form][zone]' => 'Test zone (test_zone)',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $zone = Zone::load($edit['id']);
    $members = $zone->getMembers();
    $this->assertTrue(count($members) == 1, 'The created zone has the correct number of members.');
    // $members is a plugin collection which doesn't support array access.
    $members_array = [];
    foreach ($members as $member) {
      $members_array[] = $member;
    }
    $first_member = reset($members_array);
    $this->assertEqual($first_member->getName(), $edit['members[0][form][name]'], 'The first created zone member has the correct name.');
    $this->assertEqual($first_member->getConfiguration()['zone'], 'test_zone', 'The first created zone member has the correct zone.');
  }

  /**
   * Tests editing a zone via UI.
   */
  function testEditZone() {
    $zone = $this->createZone([
      'id' => strtolower($this->randomMachineName(6)),
      'name' => $this->randomMachineName(),
      'scope' => $this->randomMachineName(6),
    ]);

    $this->drupalGet('admin/config/regional/zones/manage/' . $zone->id());
    $edit = [
      'name' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    \Drupal::service('entity_type.manager')->getStorage('zone')->resetCache([$zone->id()]);
    $zone = Zone::load($zone->id());
    $this->assertEqual($zone->getName(), $edit['name'], 'The zone name has been successfully changed.');
  }

  /**
   * Tests deleting a zone via UI.
   */
  function testDeleteZone() {
    $zone = $this->createZone([
      'id' => strtolower($this->randomMachineName(6)),
      'name' => $this->randomMachineName(),
      'scope' => $this->randomMachineName(6),
    ]);

    $this->drupalGet('admin/config/regional/zones/manage/' . $zone->id() . '/delete');
    $this->assertResponse(200, 'The zone delete form can be accessed.');
    $this->assertText(t('This action cannot be undone.'), 'The zone delete confirmation form is available.');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('zone')->resetCache([$zone->id()]);
    $zone_exists = (bool) Zone::load($zone->id());
    $this->assertFalse($zone_exists, 'The zone has been deleted from the database.');
  }

  /**
   * Creates a new zone entity.
   *
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new zone entity.
   */
  protected function createZone(array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('zone');
    $zone = $storage->create($values);
    $status = $zone->save();
    $this->assertEqual($status, SAVED_NEW, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $zone->getEntityType()->getLabel(),
      '%type' => $zone->id()
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $zone = $storage->load($zone->id());

    return $zone;
  }

}
