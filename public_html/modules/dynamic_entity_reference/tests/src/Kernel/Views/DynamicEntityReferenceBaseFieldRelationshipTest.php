<?php

namespace Drupal\Tests\dynamic_entity_reference\Kernel\Views;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests dynamic entity reference relationship data for base fields.
 *
 * @group dynamic_entity_reference
 *
 * @see dynamic_entity_reference_views_data()
 */
class DynamicEntityReferenceBaseFieldRelationshipTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [];

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'field',
    'entity_test',
    'dynamic_entity_reference',
  ];

  /**
   * The entity_test entities used by the test.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * Tests views der base field relationship with single referenced entities.
   */
  public function testSingleBaseFieldRelationship() {

    \Drupal::state()->set('dynamic_entity_reference_entity_test_cardinality', 1);
    $this->enableModules(['dynamic_entity_reference_entity_test']);
    static::$testViews = [
      'test_dynamic_entity_reference_entity_test_view',
      'test_dynamic_entity_reference_entity_test_mul_view',
    ];
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    ViewTestData::createTestViews(get_class($this), ['dynamic_entity_reference_entity_test']);
    // Create some test entities which link each other.
    $referenced_entity = EntityTest::create();
    $referenced_entity->save();
    $referenced_entity_mul = EntityTestMul::create();
    $referenced_entity_mul->save();

    $entity = EntityTest::create();
    $entity->dynamic_references[] = $referenced_entity;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity->id());
    $this->entities[] = $entity;

    $entity = EntityTest::create();
    $entity->dynamic_references[] = $referenced_entity_mul;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity_mul->id());
    $this->entities[] = $entity;

    $entity = EntityTestMul::create();
    $entity->dynamic_references[] = $referenced_entity;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity->id());
    $this->entities[] = $entity;

    $entity = EntityTestMul::create();
    $entity->dynamic_references[] = $referenced_entity_mul;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity_mul->id());
    $this->entities[] = $entity;

    Views::viewsData()->clear();

    // Check just the generated views data.
    $views_data_entity_test = Views::viewsData()->get('entity_test');

    // Check views data for test entity referenced from dynamic_references.
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['entity type'], 'entity_test');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['relationship field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test');
    // Check views data for test entity - data table referenced from
    // dynamic_references.
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['entity type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['relationship field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test_mul');
    // Check the backwards reference for test entity using dynamic_references.
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['entity type'], 'entity_test');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['base field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['field'], 'id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['extra'][0]['field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test');
    // Check the backwards reference for test entity - data table using
    // dynamic_references.
    $this->assertEquals($views_data_entity_test['reverse__entity_test_mul__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test['reverse__entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test['reverse__entity_test_mul__dynamic_references']['relationship']['entity type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test['reverse__entity_test_mul__dynamic_references']['relationship']['base field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test_mul__dynamic_references']['relationship']['field'], 'id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test_mul__dynamic_references']['relationship']['extra'][0]['field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test['reverse__entity_test_mul__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test');

    // Check just the generated views data.
    $views_data_entity_test_mul = Views::viewsData()->get('entity_test_mul_property_data');

    // Check views data for test entity referenced from dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['entity type'], 'entity_test');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['relationship field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test');
    // Check views data for test entity - data table referenced from
    // dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['entity type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['relationship field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test_mul');
    // Check the backwards reference for test entity using dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test__dynamic_references']['relationship']['entity type'], 'entity_test');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test__dynamic_references']['relationship']['base field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test__dynamic_references']['relationship']['field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test__dynamic_references']['relationship']['extra'][0]['field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test_mul');
    // Check the backwards reference for test entity - data table using
    // dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['entity type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['base field'], 'dynamic_references__target_id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['extra'][0]['field'], 'dynamic_references__target_type');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test_mul');

    // Check an actual base table entity view with base table entity for
    // single value der base field.
    $view = Views::getView('test_dynamic_entity_reference_entity_test_view');
    $this->executeView($view);
    $ids = [2, 3];
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test');
      if (!$index) {
        // Test the relationship.
        $this->assertEquals($row->entity_test_entity_test_id, 1);

        // Test that the correct relationship entity is on the row.
        $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->id(), 1);
        $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->bundle(), 'entity_test');
      }
      else {
        // Test the relationship.
        $this->assertTrue(!isset($row->entity_test_entity_test_id));

        // Test that the correct relationship entity is on the row.
        $this->assertTrue(empty($row->_relationship_entities));
      }
    }

    $view->destroy();
    // Check an actual base table entity view with data table entity for
    // single value der base field.
    $view->setDisplay('embed_1');
    $this->executeView($view);

    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test');
      if ($index) {
        // Test the relationship.
        $this->assertEquals($row->entity_test_mul_property_data_entity_test_id, 1);

        // Test that the correct relationship entity is on the row.
        $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->id(), 1);
        $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');
      }
      else {
        // Test the relationship.
        $this->assertTrue(!isset($row->entity_test_mul_property_data_entity_test_id));

        // Test that the correct relationship entity is on the row.
        $this->assertTrue(empty($row->_relationship_entities));
      }
    }

    $view->destroy();
    // Check the backwards reference view of base table entity with base table
    // entity for single value der base field.
    $view->setDisplay('embed_2');
    $this->executeView($view);

    $row = reset($view->result);
    // Just check that the actual ID of the entity is the expected one.
    $this->assertEquals($row->id, 1);
    // Also check that we have the correct result entity.
    $this->assertEquals($row->_entity->id(), 1);
    $this->assertEquals($row->_entity->bundle(), 'entity_test');
    // Test the relationship.
    $this->assertEquals($row->entity_test_entity_test_id, 2);

    // Test that the correct relationship entity is on the row.
    $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->id(), 2);
    $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->bundle(), 'entity_test');

    $view->destroy();
    // Check the backwards reference view of base table entity with base table
    // entity for single value der base field.
    $view->setDisplay('embed_3');
    $this->executeView($view);

    $row = reset($view->result);
    // Just check that the actual ID of the entity is the expected one.
    $this->assertEquals($row->id, 1);
    // Also check that we have the correct result entity.
    $this->assertEquals($row->_entity->id(), 1);
    $this->assertEquals($row->_entity->bundle(), 'entity_test');
    // Test the relationship.
    $this->assertEquals($row->entity_test_mul_property_data_entity_test_id, 2);

    // Test that the correct relationship entity is on the row.
    $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->id(), 2);
    $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');

    $view->destroy();
    // Check an actual data table entity view with data table entity for
    // single value der base field.
    $view = Views::getView('test_dynamic_entity_reference_entity_test_mul_view');
    $this->executeView($view);
    $ids = [2, 3];
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
      if ($index) {
        // Test the relationship.
        $this->assertEquals($row->entity_test_mul_property_data_entity_test_mul_property_data_, 1);

        // Test that the correct relationship entity is on the row.
        $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->id(), 1);
        $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');
      }
      else {
        // Test the relationship.
        $this->assertTrue(!isset($row->entity_test_mul_property_data_entity_test_mul_property_data_));

        // Test that the correct relationship entity is on the row.
        $this->assertTrue(empty($row->_relationship_entities));
      }
    }

    $view->destroy();
    // Check an actual data table entity view with base table entity for
    // single value der base field.
    $view->setDisplay('embed_1');
    $this->executeView($view);

    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
      if (!$index) {
        // Test the relationship.
        $this->assertEquals($row->entity_test_entity_test_mul_property_data_id, 1);

        // Test that the correct relationship entity is on the row.
        $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->id(), 1);
        $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->bundle(), 'entity_test');
      }
      else {
        // Test the relationship.
        $this->assertTrue(!isset($row->entity_test_entity_test_mul_property_data_id));

        // Test that the correct relationship entity is on the row.
        $this->assertTrue(empty($row->_relationship_entities));
      }
    }

    $view->destroy();
    // Check the backwards reference view of data table entity with data table
    // entity for single value der base field.
    $view->setDisplay('embed_2');
    $this->executeView($view);

    $row = reset($view->result);
    // Just check that the actual ID of the entity is the expected one.
    $this->assertEquals($row->id, 1);
    // Also check that we have the correct result entity.
    $this->assertEquals($row->_entity->id(), 1);
    $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
    // Test the relationship.
    $this->assertEquals($row->entity_test_mul_property_data_entity_test_mul_property_data_, 3);

    // Test that the correct relationship entity is on the row.
    $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->id(), 3);
    $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');

    $view->destroy();
    // Check the backwards reference view of data table entity with base table
    // entity for single value der base field.
    $view->setDisplay('embed_3');
    $this->executeView($view);

    $row = reset($view->result);
    // Just check that the actual ID of the entity is the expected one.
    $this->assertEquals($row->id, 1);
    // Also check that we have the correct result entity.
    $this->assertEquals($row->_entity->id(), 1);
    $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
    // Test the relationship.
    $this->assertEquals($row->entity_test_entity_test_mul_property_data_id, 3);

    // Test that the correct relationship entity is on the row.
    $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->id(), 3);
    $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->bundle(), 'entity_test');
  }

  /**
   * Tests views der base field relationship with multiple referenced entities.
   */
  public function testMultiBaseFieldRelationship() {

    \Drupal::state()->set('dynamic_entity_reference_entity_test_cardinality', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->enableModules(['dynamic_entity_reference_entity_test']);
    static::$testViews = [
      'test_dynamic_entity_reference_mul_entity_test_view',
      'test_dynamic_entity_reference_mul_entity_test_mul_view',
    ];
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');

    // First verify the cadinality is set properly.
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $this->assertEquals($entity_field_manager->getBaseFieldDefinitions('entity_test')['dynamic_references']->getCardinality(), FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->assertEquals($entity_field_manager->getBaseFieldDefinitions('entity_test_mul')['dynamic_references']->getCardinality(), FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    ViewTestData::createTestViews(get_class($this), ['dynamic_entity_reference_entity_test']);

    // Create some test entities which link each other.
    $referenced_entity = EntityTest::create();
    $referenced_entity->save();
    $referenced_entity_mul = EntityTestMul::create();
    $referenced_entity_mul->save();

    $entity = EntityTest::create();
    $entity->dynamic_references[] = $referenced_entity;
    $entity->dynamic_references[] = $referenced_entity_mul;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity->id());
    $this->assertEquals($entity->dynamic_references[1]->entity->id(), $referenced_entity_mul->id());
    $this->entities[] = $entity;

    $entity = EntityTest::create();
    $entity->dynamic_references[] = $referenced_entity;
    $entity->dynamic_references[] = $referenced_entity_mul;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity->id());
    $this->assertEquals($entity->dynamic_references[1]->entity->id(), $referenced_entity_mul->id());
    $this->entities[] = $entity;

    $entity = EntityTestMul::create();
    $entity->dynamic_references[] = $referenced_entity;
    $entity->dynamic_references[] = $referenced_entity_mul;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity->id());
    $this->assertEquals($entity->dynamic_references[1]->entity->id(), $referenced_entity_mul->id());
    $this->entities[] = $entity;

    $entity = EntityTestMul::create();
    $entity->dynamic_references[] = $referenced_entity;
    $entity->dynamic_references[] = $referenced_entity_mul;
    $entity->save();
    $this->assertEquals($entity->dynamic_references[0]->entity->id(), $referenced_entity->id());
    $this->assertEquals($entity->dynamic_references[1]->entity->id(), $referenced_entity_mul->id());
    $this->entities[] = $entity;

    Views::viewsData()->clear();

    // Check just the generated views data.
    $views_data_entity_test = Views::viewsData()->get('entity_test__dynamic_references');
    // Check views data for test entity referenced from dynamic_references.
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['entity type'], 'entity_test');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['relationship field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test['entity_test__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test');
    // Check views data for test entity - data table referenced from
    // dynamic_references.
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['entity type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['relationship field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test['entity_test_mul__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test_mul');

    // Check the backwards reference for test entity using field_test.
    $views_data_entity_test = Views::viewsData()->get('entity_test');

    // Check the backwards reference for test entity using dynamic_references.
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['id'], 'entity_reverse');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['entity_type'], 'entity_test');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['field_name'], 'dynamic_references');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['field table'], 'entity_test__dynamic_references');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['field field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['join_extra'][0]['field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['join_extra'][0]['value'], 'entity_test');

    // Check the backwards reference for test entity using field_test.
    $views_data_entity_test = Views::viewsData()->get('entity_test_mul_property_data');

    // Check the backwards reference for test entity - data table using
    // dynamic_references.
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['id'], 'entity_reverse');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['entity_type'], 'entity_test');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['field_name'], 'dynamic_references');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['field table'], 'entity_test__dynamic_references');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['field field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['join_extra'][0]['field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test['reverse__entity_test__dynamic_references']['relationship']['join_extra'][0]['value'], 'entity_test_mul');

    // Check just the generated views data.
    $views_data_entity_test_mul = Views::viewsData()->get('entity_test_mul__dynamic_references');

    // Check views data for test entity referenced from dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['base'], 'entity_test');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['entity type'], 'entity_test');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['relationship field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test_mul['entity_test__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test');
    // Check views data for test entity - data table referenced from
    // dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['id'], 'standard');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['entity type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['relationship field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['extra'][0]['left_field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test_mul['entity_test_mul__dynamic_references']['relationship']['extra'][0]['value'], 'entity_test_mul');

    // Check the backwards reference for test entity using field_test.
    $views_data_entity_test_mul = Views::viewsData()->get('entity_test');

    // Check the backwards reference for test entity using dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['id'], 'entity_reverse');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['entity_type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['field_name'], 'dynamic_references');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['field table'], 'entity_test_mul__dynamic_references');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['field field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['join_extra'][0]['field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['join_extra'][0]['value'], 'entity_test');

    // Check the backwards reference for test entity using field_test.
    $views_data_entity_test_mul = Views::viewsData()->get('entity_test_mul_property_data');

    // Check the backwards reference for test entity - data table using
    // dynamic_references.
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['id'], 'entity_reverse');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['base'], 'entity_test_mul_property_data');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['entity_type'], 'entity_test_mul');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['base field'], 'id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['field_name'], 'dynamic_references');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['field table'], 'entity_test_mul__dynamic_references');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['field field'], 'dynamic_references_target_id');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['join_extra'][0]['field'], 'dynamic_references_target_type');
    $this->assertEquals($views_data_entity_test_mul['reverse__entity_test_mul__dynamic_references']['relationship']['join_extra'][0]['value'], 'entity_test_mul');

    // Check an actual base table entity view with base table entity for
    // multiple value der base field.
    $view = Views::getView('test_dynamic_entity_reference_mul_entity_test_view');
    $this->executeView($view);
    $ids = [2, 3];
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test');
      // Test the relationship.
      $this->assertEquals($row->entity_test_entity_test__dynamic_references_id, 1);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->id(), 1);
      $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->bundle(), 'entity_test');
    }

    $view->destroy();
    // Check an actual base table entity view with data table entity for
    // multiple value der base field.
    $view->setDisplay('embed_1');
    $this->executeView($view);
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test');
      // Test the relationship.
      $this->assertEquals($row->entity_test_mul_property_data_entity_test__dynamic_reference, 1);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->id(), 1);
      $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');
    }

    $view->destroy();
    // Check the backwards reference view of base table entity with base table
    // entity for multiple value der base field.
    $view->setDisplay('embed_2');
    $this->executeView($view);
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, 1);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), 1);
      $this->assertEquals($row->_entity->bundle(), 'entity_test');
      // Test the relationship.
      $this->assertEquals($row->dynamic_references_entity_test_id, $ids[$index]);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->id(), $ids[$index]);
      $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->bundle(), 'entity_test');
    }

    $view->destroy();
    // Check the backwards reference view of base table entity with data table
    // entity for multiple value der base field.
    $view->setDisplay('embed_3');
    $this->executeView($view);
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, 1);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), 1);
      $this->assertEquals($row->_entity->bundle(), 'entity_test');
      // Test the relationship.
      $this->assertEquals($row->dynamic_references_entity_test_id, $ids[$index]);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->id(), $ids[$index]);
      $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');
    }

    // Check an actual data table entity view with data table entity for
    // multiple value der base field.
    $view = Views::getView('test_dynamic_entity_reference_mul_entity_test_mul_view');
    $this->executeView($view);
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
      // Test the relationship.
      $this->assertEquals($row->entity_test_mul_property_data_entity_test_mul__dynamic_refer, 1);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->id(), 1);
      $this->assertEquals($row->_relationship_entities['entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');
    }

    $view->destroy();
    // Check an actual data table entity view with base table entity for
    // multiple value der base field.
    $view->setDisplay('embed_1');
    $this->executeView($view);
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, $ids[$index]);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), $ids[$index]);
      $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
      // Test the relationship.
      $this->assertEquals($row->entity_test_entity_test_mul__dynamic_references_id, 1);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->id(), 1);
      $this->assertEquals($row->_relationship_entities['entity_test__dynamic_references']->bundle(), 'entity_test');
    }

    $view->destroy();
    // Check the backwards reference view of data table entity with data table
    // entity for multiple value der base field.
    $view->setDisplay('embed_2');
    $this->executeView($view);
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, 1);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), 1);
      $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
      // Test the relationship.
      $this->assertEquals($row->dynamic_references_entity_test_mul_property_data_id, $ids[$index]);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->id(), $ids[$index]);
      $this->assertEquals($row->_relationship_entities['reverse__entity_test_mul__dynamic_references']->bundle(), 'entity_test_mul');
    }

    $view->destroy();
    // Check the backwards reference view of data table entity with base table
    // entity for multiple value der base field.
    $view->setDisplay('embed_3');
    $this->executeView($view);
    foreach ($view->result as $index => $row) {
      // Just check that the actual ID of the entity is the expected one.
      $this->assertEquals($row->id, 1);
      // Also check that we have the correct result entity.
      $this->assertEquals($row->_entity->id(), 1);
      $this->assertEquals($row->_entity->bundle(), 'entity_test_mul');
      // Test the relationship.
      $this->assertEquals($row->dynamic_references_entity_test_mul_property_data_id, $ids[$index]);

      // Test that the correct relationship entity is on the row.
      $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->id(), $ids[$index]);
      $this->assertEquals($row->_relationship_entities['reverse__entity_test__dynamic_references']->bundle(), 'entity_test');
    }

  }

}
