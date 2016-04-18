<?php

/**
 * @file
 * Definition of Drupal\field_group\Tests\ManageDisplayTest.
 */

namespace Drupal\field_group\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for managing display of entities.
 *
 * @group field_group
 */
class ManageDisplayTest extends WebTestBase {

  use FieldGroupTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'field_group');

  function setUp() {

    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'bypass node access'));
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name =  Unicode::strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->id();

  }

  /**
   * Test the creation a group on the article content type.
   */
  function testCreateGroup() {

    // Create random group name.
    $this->group_label = $this->randomString(8);
    $this->group_name_input = Unicode::strtolower($this->randomMachineName());
    $this->group_name = 'group_' . $this->group_name_input;

    // Setup new group.
    $group = array(
      'fields[_add_new_group][label]' => $this->group_label,
      'fields[_add_new_group][group_name]' => $this->group_name_input,
    );

    // Add new group on the 'Manage form display' page.
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/form-display', $group, t('Save'));

    $this->assertRaw(t('New group %label successfully created.', array('%label' => $this->group_label)), t('Group message displayed on screen.'));

    // Test if group is in the $groups array.
    $this->group = field_group_load_field_group($this->group_name, 'node', $this->type, 'form', 'default');
    $this->assertNotNull($this->group, t('Group was loaded'));

    // Add new group on the 'Manage display' page.
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $group, t('Save'));
    $this->assertRaw(t('New group %label successfully created.', array('%label' => $this->group_label)), t('Group message displayed on screen.'));

    // Test if group is in the $groups array.
    $loaded_group = field_group_load_field_group($this->group_name, 'node', $this->type, 'view', 'default');
    $this->assertNotNull($loaded_group, t('Group was loaded'));
  }

  /**
   * Delete a group.
   */
  function testDeleteGroup() {

    $data = array(
      'format_type' => 'fieldset',
      'label' => 'testing',
    );

    $group = $this->createGroup('node', $this->type, 'form', 'default', $data);

    $config_name = 'node.' . $this->type . '.form.default.' . $group->group_name;

    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/groups/' . $config_name . '/delete', array(), t('Delete'));
    $this->assertRaw(t('The group %label has been deleted from the %type content type.', array('%label' => $group->label, '%type' => $this->type)), t('Group removal message displayed on screen.'));

    $display = EntityFormDisplay::load($group->entity_type . '.' . $group->bundle . '.' . $group->mode);
    $data = $display->getThirdPartySettings('field_group');
    debug($data);

    // Test that group is not in the $groups array.
    \Drupal::entityManager()->getStorage('entity_form_display')->resetCache();
    $loaded_group = field_group_load_field_group($group->group_name, 'node', $this->type, 'form', 'default');
    debug($loaded_group);
    $this->assertNull($loaded_group, t('Group not found after deleting'));

    $data = array(
      'format_type' => 'fieldset',
      'label' => 'testing',
    );

    $group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $config_name = 'node.' . $this->type . '.view.default.' . $group->group_name;

    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/groups/' . $config_name . '/delete', array(), t('Delete'));
    $this->assertRaw(t('The group %label has been deleted from the %type content type.', array('%label' => $group->label, '%type' => $this->type)), t('Group removal message displayed on screen.'));

    // Test that group is not in the $groups array.
    \Drupal::entityManager()->getStorage('entity_view_display')->resetCache();
    $loaded_group = field_group_load_field_group($group->group_name, 'node', $this->type, 'view', 'default');
    debug($loaded_group);
    $this->assertNull($loaded_group, t('Group not found after deleting'));
  }

  /**
   * Nest a field underneath a group.
   */
  function testNestField() {

    $data = array(
      'format_type' => 'fieldset',
    );

    $group = $this->createGroup('node', $this->type, 'form', 'default', $data);

    $edit = array(
      'fields[body][parent]' => $group->group_name,
    );
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/form-display', $edit, t('Save'));
    $this->assertRaw(t('Your settings have been saved.'), t('Settings saved'));

    $group = field_group_load_field_group($group->group_name, 'node', $this->type, 'form', 'default');
    $this->assertTrue(in_array('body', $group->children), t('Body is a child of %group', array('%group' => $group->group_name)));
  }

}
