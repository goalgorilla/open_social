<?php

namespace Drupal\dynamic_entity_reference\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests dynamic entity reference field widgets.
 *
 * @group dynamic_entity_reference
 */
class DynamicEntityReferenceWidgetTest extends WebTestBase {

  /**
   * A user with permission to administer content types, node fields, etc.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array(
    'dynamic_entity_reference',
    'field_ui',
    'node',
  );

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = array(
    'access content',
    'administer content types',
    'administer node fields',
    'administer node form display',
    'bypass node access',
  );

  /**
   * Sets up a Drupal site for running functional and integration tests.
   */
  protected function setUp() {
    parent::setUp();

    // Create default content type.
    $this->drupalCreateContentType(array('type' => 'reference_content'));
    $this->drupalCreateContentType(array('type' => 'referenced_content'));

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);

    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'dynamic_entity_reference',
      'settings' => array(
        'exclude_entity_types' => FALSE,
        'entity_type_ids' => [
          'node',
        ],
      ),
    ));
    $field_storage->save();
    FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'reference_content',
      'settings' => array(
        'node' => array(
          'handler' => 'default',
          'handler_settings' => array(
            'target_bundles' => array('referenced_content'),
            'sort' => array('field' => '_none'),
          ),
        ),
      ),
    ))->save();
    $this->fieldName = $field_name;
  }

  /**
   * Tests default autocomplete widget.
   */
  public function testEntityReferenceDefaultWidget() {
    $field_name = $this->fieldName;
    entity_get_form_display('node', 'reference_content', 'default')
      ->setComponent($field_name, array(
        'type' => 'dynamic_entity_reference_default',
      ))
      ->save();
    $this->drupalLogin($this->adminUser);
    // Create a node to be referenced.
    $referenced_node = $this->drupalCreateNode(array('type' => 'referenced_content'));
    $title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $title,
      $field_name . '[0][target_type]' => $referenced_node->getEntityTypeId(),
      $field_name . '[0][target_id]' => $referenced_node->getTitle() . ' (' . $referenced_node->id() . ')',
    );
    $this->drupalPostForm(Url::fromRoute('node.add', array('node_type' => 'reference_content')), $edit, t('Save'));
    $this->assertRaw(t('@type %title has been created.', array('@type' => 'reference_content', '%title' => $title)));
    $nodes = \Drupal::entityManager()->getStorage('node')->loadByProperties(array('title' => $title));
    $reference_node = reset($nodes);
    $this->assertEqual($reference_node->get($field_name)->offsetGet(0)->target_type, $referenced_node->getEntityTypeId());
    $this->assertEqual($reference_node->get($field_name)->offsetGet(0)->target_id, $referenced_node->id());
  }

  /**
   * Tests option button widget.
   */
  public function testEntityReferenceOptionsButtonsWidget() {
    $field_name = $this->fieldName;
    entity_get_form_display('node', 'reference_content', 'default')
      ->setComponent($field_name, array(
        'type' => 'dynamic_entity_reference_options_buttons',
      ))
      ->save();
    $this->drupalLogin($this->adminUser);
    // Create a node to be referenced.
    $referenced_node = $this->drupalCreateNode(array('type' => 'referenced_content'));
    $title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $title,
      $field_name => $referenced_node->getEntityTypeId() . '-' . $referenced_node->id(),
    );
    $this->drupalPostForm(Url::fromRoute('node.add', array('node_type' => 'reference_content')), $edit, t('Save'));
    $this->assertRaw(t('@type %title has been created.', array('@type' => 'reference_content', '%title' => $title)));
    $nodes = \Drupal::entityManager()->getStorage('node')->loadByProperties(array('title' => $title));
    $reference_node = reset($nodes);
    $this->assertEqual($reference_node->get($field_name)->offsetGet(0)->target_type, $referenced_node->getEntityTypeId());
    $this->assertEqual($reference_node->get($field_name)->offsetGet(0)->target_id, $referenced_node->id());
  }

  /**
   * Tests option select widget.
   */
  public function testEntityReferenceOptionsSelectWidget() {
    $field_name = $this->fieldName;
    entity_get_form_display('node', 'reference_content', 'default')
      ->setComponent($field_name, array(
        'type' => 'dynamic_entity_reference_options_select',
      ))
      ->save();
    $this->drupalLogin($this->adminUser);
    // Create a node to be referenced.
    $referenced_node = $this->drupalCreateNode(array('type' => 'referenced_content'));
    $title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $title,
      $field_name => $referenced_node->getEntityTypeId() . '-' . $referenced_node->id(),
    );
    $this->drupalPostForm(Url::fromRoute('node.add', array('node_type' => 'reference_content')), $edit, t('Save'));
    $this->assertRaw(t('@type %title has been created.', array('@type' => 'reference_content', '%title' => $title)));
    $nodes = \Drupal::entityManager()->getStorage('node')->loadByProperties(array('title' => $title));
    $reference_node = reset($nodes);
    $this->assertEqual($reference_node->get($field_name)->offsetGet(0)->target_type, $referenced_node->getEntityTypeId());
    $this->assertEqual($reference_node->get($field_name)->offsetGet(0)->target_id, $referenced_node->id());
  }

}
