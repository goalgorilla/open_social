<?php

namespace Drupal\dynamic_entity_reference\Tests;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\CssSelector\CssSelector;

/**
 * Ensures that Dynamic Entity References field works correctly.
 *
 * @group dynamic_entity_reference
 */
class DynamicEntityReferenceTest extends WebTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The another user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $anotherUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'entity_reference',
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
    'access user profiles',
  );

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->anotherUser = $this->drupalCreateUser();
  }

  /**
   * Tests field settings of dynamic entity reference field.
   */
  public function testFieldSettings() {
    // Add EntityTestBundle for EntityTestWithBundle.
    EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();
    $this->drupalLogin($this->adminUser);
    // Add a new dynamic entity reference field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = array(
      'label' => 'Foobar',
      'field_name' => 'foobar',
      'new_storage_type' => 'dynamic_entity_reference',
    );
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings[entity_type_ids][]' => 'user',
    ), t('Save field settings'));
    $this->assertFieldByName('default_value_input[field_foobar][0][target_type]');
    $this->assertFieldByXPath(CssSelector::toXPath('select[name="default_value_input[field_foobar][0][target_type]"] > option[value=entity_test]'), 'entity_test');
    $this->assertNoFieldByXPath(CssSelector::toXPath('select[name="default_value_input[field_foobar][0][target_type]"] > option[value=user]'), 'user');
    $edit = array(
      'settings[entity_test_label][handler_settings][target_bundles][entity_test_label]' => TRUE,
      'settings[entity_test_view_builder][handler_settings][target_bundles][entity_test_view_builder]' => TRUE,
      'settings[entity_test_no_id][handler_settings][target_bundles][entity_test_no_id]' => TRUE,
      'settings[entity_test_no_label][handler_settings][target_bundles][entity_test_no_label]' => TRUE,
      'settings[entity_test_label_callback][handler_settings][target_bundles][entity_test_label_callback]' => TRUE,
      'settings[entity_test][handler_settings][target_bundles][entity_test]' => TRUE,
      'settings[entity_test_admin_routes][handler_settings][target_bundles][entity_test_admin_routes]' => TRUE,
      'settings[entity_test_base_field_display][handler_settings][target_bundles][entity_test_base_field_display]' => TRUE,
      'settings[entity_test_mul][handler_settings][target_bundles][entity_test_mul]' => TRUE,
      'settings[entity_test_mul_changed][handler_settings][target_bundles][entity_test_mul_changed]' => TRUE,
      'settings[entity_test_mul_default_value][handler_settings][target_bundles][entity_test_mul_default_value]' => TRUE,
      'settings[entity_test_mul_langcode_key][handler_settings][target_bundles][entity_test_mul_langcode_key]' => TRUE,
      'settings[entity_test_rev][handler_settings][target_bundles][entity_test_rev]' => TRUE,
      'settings[entity_test_mulrev_changed][handler_settings][target_bundles][entity_test_mulrev_changed]' => TRUE,
      'settings[entity_test_mulrev][handler_settings][target_bundles][entity_test_mulrev]' => TRUE,
      'settings[entity_test_revlog][handler_settings][target_bundles][entity_test_revlog]' => TRUE,
      'settings[entity_test_constraints][handler_settings][target_bundles][entity_test_constraints]' => TRUE,
      'settings[entity_test_composite_constraint][handler_settings][target_bundles][entity_test_composite_constraint]' => TRUE,
      'settings[entity_test_constraint_violation][handler_settings][target_bundles][entity_test_constraint_violation]' => TRUE,
      'settings[entity_test_field_override][handler_settings][target_bundles][entity_test_field_override]' => TRUE,
      'settings[entity_test_default_value][handler_settings][target_bundles][entity_test_default_value]' => TRUE,
      'settings[entity_test_update][handler_settings][target_bundles][entity_test_update]' => TRUE,
      'settings[entity_test_with_bundle][handler_settings][target_bundles][test]' => TRUE,
      'settings[entity_test_default_access][handler_settings][target_bundles][entity_test_default_access]' => TRUE,
      'settings[entity_test_cache][handler_settings][target_bundles][entity_test_cache]' => TRUE,
      'settings[entity_test_string_id][handler_settings][target_bundles][entity_test_string_id]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertRaw(t('Saved %name configuration', array('%name' => 'Foobar')));
    $excluded_entity_type_ids = FieldStorageConfig::loadByName('entity_test', 'field_foobar')
      ->getSetting('entity_type_ids');
    $this->assertNotNull($excluded_entity_type_ids);
    $this->assertIdentical(array_keys($excluded_entity_type_ids), array('user'));
    // Check the include entity settings.
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar/storage');
    $this->drupalPostForm(NULL, array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings[exclude_entity_types]' => FALSE,
      'settings[entity_type_ids][]' => 'user',
    ), t('Save field settings'));
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar');
    $this->assertFieldByName('default_value_input[field_foobar][0][target_type]');
    $this->assertFieldByXPath(CssSelector::toXPath('select[name="default_value_input[field_foobar][0][target_type]"] > option[value=user]'), 'user');
    $this->assertNoFieldByXPath(CssSelector::toXPath('select[name="default_value_input[field_foobar][0][target_type]"] > option[value=entity_test]'), 'entity_test');
    $this->drupalPostForm(NULL, array(), t('Save settings'));
    $this->assertRaw(t('Saved %name configuration', array('%name' => 'Foobar')));
    $excluded_entity_type_ids = FieldStorageConfig::loadByName('entity_test', 'field_foobar')
      ->getSetting('entity_type_ids');
    $this->assertNotNull($excluded_entity_type_ids);
    $this->assertIdentical(array_keys($excluded_entity_type_ids), array('user'));
    // Check the default settings.
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.field_foobar');
    $this->drupalPostForm(NULL, array(
      'default_value_input[field_foobar][0][target_type]' => 'user',
      'default_value_input[field_foobar][0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ), t('Save settings'));

    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', 'field_foobar')->toArray();
    $this->assertEqual($field_config['default_value']['0'], array('target_type' => 'user', 'target_uuid' => $this->adminUser->uuid()));

  }

  /**
   * Tests adding and editing values using dynamic entity reference.
   */
  public function testDynamicEntityReference() {
    // Add EntityTestBundle for EntityTestWithBundle.
    EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();
    $this->drupalLogin($this->adminUser);
    // Add a new dynamic entity reference field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = array(
      'label' => 'Foobar',
      'field_name' => 'foobar',
      'new_storage_type' => 'dynamic_entity_reference',
    );
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ), t('Save field settings'));

    $edit = array(
      'settings[entity_test_label][handler_settings][target_bundles][entity_test_label]' => TRUE,
      'settings[entity_test_view_builder][handler_settings][target_bundles][entity_test_view_builder]' => TRUE,
      'settings[entity_test_no_id][handler_settings][target_bundles][entity_test_no_id]' => TRUE,
      'settings[entity_test_no_label][handler_settings][target_bundles][entity_test_no_label]' => TRUE,
      'settings[entity_test_label_callback][handler_settings][target_bundles][entity_test_label_callback]' => TRUE,
      'settings[entity_test][handler_settings][target_bundles][entity_test]' => TRUE,
      'settings[entity_test_admin_routes][handler_settings][target_bundles][entity_test_admin_routes]' => TRUE,
      'settings[entity_test_base_field_display][handler_settings][target_bundles][entity_test_base_field_display]' => TRUE,
      'settings[entity_test_mul][handler_settings][target_bundles][entity_test_mul]' => TRUE,
      'settings[entity_test_mul_changed][handler_settings][target_bundles][entity_test_mul_changed]' => TRUE,
      'settings[entity_test_mul_default_value][handler_settings][target_bundles][entity_test_mul_default_value]' => TRUE,
      'settings[entity_test_mul_langcode_key][handler_settings][target_bundles][entity_test_mul_langcode_key]' => TRUE,
      'settings[entity_test_rev][handler_settings][target_bundles][entity_test_rev]' => TRUE,
      'settings[entity_test_mulrev_changed][handler_settings][target_bundles][entity_test_mulrev_changed]' => TRUE,
      'settings[entity_test_mulrev][handler_settings][target_bundles][entity_test_mulrev]' => TRUE,
      'settings[entity_test_revlog][handler_settings][target_bundles][entity_test_revlog]' => TRUE,
      'settings[entity_test_constraints][handler_settings][target_bundles][entity_test_constraints]' => TRUE,
      'settings[entity_test_composite_constraint][handler_settings][target_bundles][entity_test_composite_constraint]' => TRUE,
      'settings[entity_test_constraint_violation][handler_settings][target_bundles][entity_test_constraint_violation]' => TRUE,
      'settings[entity_test_field_override][handler_settings][target_bundles][entity_test_field_override]' => TRUE,
      'settings[entity_test_default_value][handler_settings][target_bundles][entity_test_default_value]' => TRUE,
      'settings[entity_test_update][handler_settings][target_bundles][entity_test_update]' => TRUE,
      'settings[entity_test_with_bundle][handler_settings][target_bundles][test]' => TRUE,
      'settings[entity_test_default_access][handler_settings][target_bundles][entity_test_default_access]' => TRUE,
      'settings[entity_test_cache][handler_settings][target_bundles][entity_test_cache]' => TRUE,
      'settings[entity_test_string_id][handler_settings][target_bundles][entity_test_string_id]' => TRUE,

    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertRaw(t('Saved %name configuration', array('%name' => 'Foobar')));
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

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
    $this->assertField('field_foobar[0][target_id]', 'Found foobar field target id');
    $this->assertField('field_foobar[0][target_type]', 'Found foobar field target type');

    // Ensure that the autocomplete path is correct.
    $input = $this->xpath('//input[@name=:name]', array(':name' => 'field_foobar[0][target_id]'))[0];
    $settings = FieldConfig::loadByName('entity_test', 'entity_test', 'field_foobar')->getSettings();
    $selection_settings = $settings['entity_test_label']['handler_settings'] ?: [];
    $data = serialize($selection_settings) . 'entity_test_label' . $settings['entity_test_label']['handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
    $expected_autocomplete_path = Url::fromRoute('system.entity_autocomplete', array(
      'target_type' => 'entity_test_label',
      'selection_handler' => $settings['entity_test_label']['handler'],
      'selection_settings_key' => $selection_settings_key,
    ))->toString();
    $this->assertTrue(strpos((string) $input['data-autocomplete-path'], $expected_autocomplete_path) !== FALSE);

    // Add some extra dynamic entity reference fields.
    $this->drupalPostAjaxForm(NULL, array(), array('field_foobar_add_more' => t('Add another item')), NULL, array(), array(), 'entity-test-entity-test-form');
    $this->drupalPostAjaxForm(NULL, array(), array('field_foobar_add_more' => t('Add another item')), NULL, array(), array(), 'entity-test-entity-test-form');

    $edit = array(
      'field_foobar[0][target_id]' => $this->anotherUser->label() . ' (' . $this->anotherUser->id() . ')',
      'field_foobar[0][target_type]' => 'user',
      // Ensure that an exact match on a unique label is accepted.
      'field_foobar[1][target_id]' => 'item1',
      'field_foobar[1][target_type]' => 'entity_test',
      'field_foobar[2][target_id]' => 'item2 (' . $item2->id() . ')',
      'field_foobar[2][target_type]' => 'entity_test',
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
    $this->assertText($this->anotherUser->label());
    $this->assertText('item1');
    $this->assertText('item2');

    $this->assertEqual(count($entity->field_foobar), 3, 'Three items in field');
    $this->assertEqual($entity->field_foobar[0]->entity->label(), $this->anotherUser->label());
    $this->assertEqual($entity->field_foobar[1]->entity->label(), 'item1');
    $this->assertEqual($entity->field_foobar[2]->entity->label(), 'item2');

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    // Ensure that the autocomplete path is correct.
    foreach (array('0' => 'user', '1' => 'entity_test', '2' => 'entity_test') as $index => $expected_entity_type) {
      $selection_settings = $settings[$expected_entity_type]['handler_settings'] ?: [];
      $data = serialize($selection_settings) . $expected_entity_type . $settings[$expected_entity_type]['handler'];
      $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
      $input = $this->xpath('//input[@name=:name]', array(':name' => 'field_foobar[' . $index . '][target_id]'))[0];
      $expected_autocomplete_path = Url::fromRoute('system.entity_autocomplete', array(
        'target_type' => $expected_entity_type,
        'selection_handler' => $settings[$expected_entity_type]['handler'],
        'selection_settings_key' => $selection_settings_key,
      ))->toString();
      $this->assertTrue(strpos((string) $input['data-autocomplete-path'], $expected_autocomplete_path) !== FALSE);
    }

    $edit = array(
      'name[0][value]' => 'Bazbar',
      // Remove one child.
      'field_foobar[2][target_id]' => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache(array($entity->id()));
    $entity = EntityTest::load($entity->id());
    $this->assertEqual(count($entity->field_foobar), 2, 'Two values in field');

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
      'field_foobar[1][target_id]' => 'duplicate label',
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
      'field_foobar[1][target_id]' => 'does not exist',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('There are no entities matching "%value".', array('%value' => 'does not exist')));

    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $edit = array(
      'name[0][value]' => 'Bazbar',
      // Reference itself.
      'field_foobar[1][target_id]' => 'Bazbar (' . $entity->id() . ')',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Bazbar');
    // Reload entity.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->resetCache(array($entity->id()));
    $entity = EntityTest::load($entity->id());
    $this->assertEqual($entity->field_foobar[1]->entity->label(), 'Bazbar');
  }

  /**
   * Tests entity auto creation using dynamic entity reference.
   */
  public function testDynamicEntityReferenceAutoCreate() {
    \Drupal::service('module_installer')->install(array('taxonomy'));
    // Update router to reflect newly installed module.
    \Drupal::service('router.builder')->rebuild();
    $vocabulary = Vocabulary::create(array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary->save();
    $term = Term::create(array(
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    $this->drupalLogin($this->adminUser);
    // Add a new dynamic entity reference field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = array(
      'label' => 'Foobar',
      'field_name' => 'foobar',
      'new_storage_type' => 'dynamic_entity_reference',
    );
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings[exclude_entity_types]' => FALSE,
      'settings[entity_type_ids][]' => array('taxonomy_term', 'user'),
    ), t('Save field settings'));
    $edit = array(
      'settings[taxonomy_term][handler_settings][target_bundles][' . $vocabulary->id() . ']' => $vocabulary->id(),
      'settings[taxonomy_term][handler_settings][auto_create]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $this->drupalGet('entity_test/add');

    // Add some extra dynamic entity reference fields.
    $this->drupalPostAjaxForm(NULL, array(), array('field_foobar_add_more' => t('Add another item')), NULL, array(), array(), 'entity-test-entity-test-form');
    $this->drupalPostAjaxForm(NULL, array(), array('field_foobar_add_more' => t('Add another item')), NULL, array(), array(), 'entity-test-entity-test-form');
    $edit = array(
      'field_foobar[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      'field_foobar[0][target_type]' => 'user',
      // Add a non-existing term.
      'field_foobar[1][target_id]' => 'tag',
      'field_foobar[1][target_type]' => 'taxonomy_term',
      'field_foobar[2][target_id]' => $term->label() . ' (' . $term->id() . ')',
      'field_foobar[2][target_type]' => 'taxonomy_term',
      'name[0][value]' => 'Barfoo',
      'user_id[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $entities = \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->loadByProperties(['name' => 'Barfoo']);
    $this->assertEqual(1, count($entities), 'Entity was saved');
    $entity = reset($entities);

    $this->assertEqual(count($entity->field_foobar), 3, 'Three items in field');
    $this->assertEqual($entity->field_foobar[0]->entity->label(), $this->adminUser->label());
    $this->assertEqual($entity->field_foobar[1]->entity->label(), 'tag');
    $this->assertEqual($entity->field_foobar[2]->entity->label(), $term->label());

    $this->drupalGet('entity_test/' . $entity->id());
    $this->assertText('Barfoo');
    $this->assertText($this->adminUser->label());
    $this->assertText('tag');
    $this->assertText($term->label());

  }

  /**
   * Tests node preview of dynamic entity reference field.
   */
  public function testNodePreview() {
    \Drupal::service('module_installer')->install(array('taxonomy', 'node'));
    $this->drupalCreateContentType(array('type' => 'article'));
    $this->permissions = array(
      'access content',
      'administer nodes',
      'administer node fields',
      'create article content',
    );
    $this->adminUser = $this->drupalCreateUser($this->permissions);

    $vocabulary = Vocabulary::create(array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary->save();

    $term = Term::create(array(
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();

    $this->drupalLogin($this->adminUser);

    // Add a new dynamic entity reference field.
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $edit = array(
      'label' => 'DER',
      'field_name' => 'der',
      'new_storage_type' => 'dynamic_entity_reference',
    );
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, array(
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings[exclude_entity_types]' => FALSE,
      'settings[entity_type_ids][]' => array('taxonomy_term'),
    ), t('Save field settings'));
    $edit = array(
      'settings[taxonomy_term][handler_settings][target_bundles][' . $vocabulary->id() . ']' => $vocabulary->id(),
      'settings[taxonomy_term][handler_settings][auto_create]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));

    // Test the node preview for existing term.
    $this->drupalGet('node/add/article');
    $this->assertField('field_der[0][target_id]', 'Found field_der field target id');
    $this->assertField('field_der[0][target_type]', 'Found field_der field target type');
    $title = $this->randomMachineName();
    $edit = array(
      'field_der[0][target_id]' => $term->label() . ' (' . $term->id() . ')',
      'field_der[0][target_type]' => 'taxonomy_term',
      'title[0][value]' => $title,
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    );

    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->assertText($title);
    $this->assertText($term->label());
    // Back to node add page.
    $this->clickLink('Back to content editing');
    $this->assertFieldByName('field_der[0][target_id]', $term->label() . ' (' . $term->id() . ')');

    // Test the node preview for new term.
    $this->drupalGet('node/add/article');
    $this->assertField('field_der[0][target_id]', 'Found field_der field target id');
    $this->assertField('field_der[0][target_type]', 'Found field_der field target type');

    $new_term = $this->randomMachineName();
    $edit = array(
      'field_der[0][target_id]' => $new_term,
      'field_der[0][target_type]' => 'taxonomy_term',
      'title[0][value]' => $title,
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    );

    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->assertText($title);
    $this->assertText($new_term);
    // Back to node add page.
    $this->clickLink('Back to content editing');
    $this->assertFieldByName('field_der[0][target_id]', $new_term);

  }

}
