<?php

namespace Drupal\dynamic_entity_reference\Tests;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that Dynamic Entity References field works correctly.
 *
 * @group dynamic_entity_reference
 */
class DynamicEntityReferenceBaseTest extends WebTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field_ui',
    'dynamic_entity_reference',
    'entity_test',
  );

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = array(
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
  );

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Tests adding and editing single values using dynamic entity reference.
   */
  public function testSingleValueDynamicEntityReference() {
    \Drupal::state()->set('dynamic_entity_reference_entity_test_cardinality', 1);
    \Drupal::service('module_installer')->install(['dynamic_entity_reference_entity_test']);
    $this->drupalLogin($this->adminUser);

    // Create some items to reference.
    $item1 = EntityTest::create([
      'name' => 'item1',
    ]);
    $item1->save();

    // Test the new entity commenting inherits default.
    $this->drupalGet('entity_test/add');
    $this->assertField('dynamic_references[0][target_id]', 'Found foobar field target id');
    $this->assertField('dynamic_references[0][target_type]', 'Found foobar field target type');

    // Ensure that the autocomplete path is correct.
    $input = $this->xpath('//input[@name=:name]', array(':name' => 'dynamic_references[0][target_id]'))[0];
    ;
    $settings = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('entity_test')['dynamic_references']->getSettings();
    $selection_settings = $settings['entity_test']['handler_settings'] ?: [];
    $data = serialize($selection_settings) . 'entity_test' . $settings['entity_test']['handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
    $expected_autocomplete_path = Url::fromRoute('system.entity_autocomplete', array(
      'target_type' => 'entity_test',
      'selection_handler' => $settings['entity_test']['handler'],
      'selection_settings_key' => $selection_settings_key,
    ))->toString();
    $this->assertTrue(strpos((string) $input['data-autocomplete-path'], $expected_autocomplete_path) !== FALSE);

    $edit = array(
      // Ensure that an exact match on a unique label is accepted.
      'dynamic_references[0][target_id]' => 'item1',
      'dynamic_references[0][target_type]' => 'entity_test',
      'name[0][value]' => 'Barfoo',
      'user_id[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $entities = \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->loadByProperties(array(
        'name' => 'Barfoo',
      ));
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Barfoo');
    $this->assertText('item1');

    $this->assertEqual(count($entity->dynamic_references), 1, 'One item in field');
    $this->assertEqual($entity->dynamic_references[0]->entity->label(), 'item1');

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $edit = array(
      'name[0][value]' => 'Bazbar',
      // Remove one child.
      'dynamic_references[0][target_id]' => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache(array($entity->id()));
    $entity = EntityTest::load($entity->id());
    $this->assertTrue($entity->dynamic_references->isEmpty(), 'No value in field');

    // Create two entities with the same label.
    $labels = array();
    $duplicates = array();
    for ($i = 0; $i < 2; $i++) {
      $duplicates[$i] = EntityTest::create([
        'name' => 'duplicate label',
      ]);
      $duplicates[$i]->save();
      $labels[$i] = $duplicates[$i]->label() . ' (' . $duplicates[$i]->id() . ')';
    }

    // Now try to submit and just specify the label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = array(
      'dynamic_references[0][target_id]' => 'duplicate label',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // We don't know the order in which the entities will be listed, so just
    // assert parts and make sure both are shown.
    $error_message = t('Multiple entities match this reference;');
    $this->assertRaw($error_message);
    $this->assertRaw($labels[0]);
    $this->assertRaw($labels[1]);

    // Create a few more to trigger the case where there are more than 5
    // matching results.
    for ($i = 2; $i < 7; $i++) {
      $duplicates[$i] = EntityTest::create([
        'name' => 'duplicate label',
      ]);
      $duplicates[$i]->save();
      $labels[$i] = $duplicates[$i]->label() . ' (' . $duplicates[$i]->id() . ')';
    }

    // Submit again with the same values.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $params = array(
      '%value' => 'duplicate label',
    );
    // We don't know which id it will display, so just assert a part of the
    // error.
    $error_message = t('Many entities are called %value. Specify the one you want by appending the id in parentheses', $params);
    $this->assertRaw($error_message);

    // Submit with a label that does not match anything.
    // Now try to submit and just specify the label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = array(
      'dynamic_references[0][target_id]' => 'does not exist',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('There are no entities matching "%value".', array('%value' => 'does not exist')));

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = array(
      'name[0][value]' => 'Bazbar',
      // Reference itself.
      'dynamic_references[0][target_id]' => 'Bazbar (' . $entity->id() . ')',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache(array($entity->id()));
    $entity = EntityTest::load($entity->id());
    $this->assertEqual($entity->dynamic_references[0]->entity->label(), 'Bazbar');
  }

  /**
   * Tests adding and editing multi values using dynamic entity reference.
   */
  public function testMultiValueDynamicEntityReference() {
    \Drupal::state()->set('dynamic_entity_reference_entity_test_cardinality', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    \Drupal::service('module_installer')->install(['dynamic_entity_reference_entity_test']);
    $this->drupalLogin($this->adminUser);

    // Create some items to reference.
    $item1 = EntityTest::create([
      'name' => 'item1',
    ]);
    $item1->save();
    $item2 = EntityTest::create([
      'name' => 'item2',
    ]);
    $item2->save();

    // Test the new entity commenting inherits default.
    $this->drupalGet('entity_test/add');
    $this->assertField('dynamic_references[0][target_id]', 'Found foobar field target id');
    $this->assertField('dynamic_references[0][target_type]', 'Found foobar field target type');

    // Ensure that the autocomplete path is correct.
    $input = $this->xpath('//input[@name=:name]', array(':name' => 'dynamic_references[0][target_id]'))[0];
    ;
    $settings = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('entity_test')['dynamic_references']->getSettings();
    $selection_settings = $settings['entity_test']['handler_settings'] ?: [];
    $data = serialize($selection_settings) . 'entity_test' . $settings['entity_test']['handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
    $expected_autocomplete_path = Url::fromRoute('system.entity_autocomplete', array(
      'target_type' => 'entity_test',
      'selection_handler' => $settings['entity_test']['handler'],
      'selection_settings_key' => $selection_settings_key,
    ))->toString();
    $this->assertTrue(strpos((string) $input['data-autocomplete-path'], $expected_autocomplete_path) !== FALSE);

    // Add some extra dynamic entity reference fields.
    $this->drupalPostAjaxForm(NULL, array(), array('dynamic_references_add_more' => t('Add another item')), NULL, array(), array(), 'entity-test-entity-test-form');

    $edit = array(
      // Ensure that an exact match on a unique label is accepted.
      'dynamic_references[0][target_id]' => 'item1',
      'dynamic_references[0][target_type]' => 'entity_test',
      'dynamic_references[1][target_id]' => 'item2 (' . $item2->id() . ')',
      'dynamic_references[1][target_type]' => 'entity_test',
      'name[0][value]' => 'Barfoo',
      'user_id[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $entities = \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->loadByProperties(array('name' => 'Barfoo'));
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Barfoo');
    $this->assertText('item1');
    $this->assertText('item2');

    $this->assertEqual(count($entity->dynamic_references), 2, 'Two items in field');
    $this->assertEqual($entity->dynamic_references[0]->entity->label(), 'item1');
    $this->assertEqual($entity->dynamic_references[1]->entity->label(), 'item2');

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $edit = array(
      'name[0][value]' => 'Bazbar',
      // Remove one child.
      'dynamic_references[1][target_id]' => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache(array($entity->id()));
    $entity = EntityTest::load($entity->id());
    $this->assertEqual(count($entity->dynamic_references), 1, 'One value in field');

    // Create two entities with the same label.
    $labels = array();
    $duplicates = array();
    for ($i = 0; $i < 2; $i++) {
      $duplicates[$i] = EntityTest::create([
        'name' => 'duplicate label',
      ]);
      $duplicates[$i]->save();
      $labels[$i] = $duplicates[$i]->label() . ' (' . $duplicates[$i]->id() . ')';
    }

    // Now try to submit and just specify the label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = array(
      'dynamic_references[1][target_id]' => 'duplicate label',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // We don't know the order in which the entities will be listed, so just
    // assert parts and make sure both are shown.
    $error_message = t('Multiple entities match this reference;');
    $this->assertRaw($error_message);
    $this->assertRaw($labels[0]);
    $this->assertRaw($labels[1]);

    // Create a few more to trigger the case where there are more than 5
    // matching results.
    for ($i = 2; $i < 7; $i++) {
      $duplicates[$i] = EntityTest::create([
        'name' => 'duplicate label',
      ]);
      $duplicates[$i]->save();
      $labels[$i] = $duplicates[$i]->label() . ' (' . $duplicates[$i]->id() . ')';
    }

    // Submit again with the same values.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $params = array(
      '%value' => 'duplicate label',
    );
    // We don't know which id it will display, so just assert a part of the
    // error.
    $error_message = t('Many entities are called %value. Specify the one you want by appending the id in parentheses', $params);
    $this->assertRaw($error_message);

    // Submit with a label that does not match anything.
    // Now try to submit and just specify the label.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = array(
      'dynamic_references[1][target_id]' => 'does not exist',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('There are no entities matching "%value".', array('%value' => 'does not exist')));

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = array(
      'name[0][value]' => 'Bazbar',
      // Reference itself.
      'dynamic_references[1][target_id]' => 'Bazbar (' . $entity->id() . ')',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache(array($entity->id()));
    $entity = EntityTest::load($entity->id());
    $this->assertEqual($entity->dynamic_references[1]->entity->label(), 'Bazbar');
  }

}
