<?php

namespace Drupal\Tests\dynamic_entity_reference\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Utility\Unicode;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the new entity API for the dynamic entity reference field type.
 *
 * @group dynamic_entity_reference
 */
class DynamicEntityReferenceItemTest extends FieldKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'dynamic_entity_reference',
    'taxonomy',
    'text',
    'filter',
    'views',
  ];

  /**
   * The taxonomy vocabulary to test with.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * The taxonomy term to test with.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    $this->vocabulary = Vocabulary::create(array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->vocabulary->save();

    $this->term = Term::create(array(
      'name' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->term->save();

    // Use the util to create a field.
    FieldStorageConfig::create(array(
      'field_name' => 'field_der',
      'type' => 'dynamic_entity_reference',
      'entity_type' => 'entity_test',
      'cardinality' => 1,
      'settings' => array(
        'exclude_entity_types' => FALSE,
        'entity_type_ids' => [
          'taxonomy_term',
        ],
      ),
    ))->save();

    FieldConfig::create(array(
      'field_name' => 'field_der',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => 'Foo Bar',
      'settings' => array(),
    ))->save();
  }

  /**
   * Tests the der field type for referencing content entities.
   */
  public function testContentEntityReferenceItem() {
    $tid = $this->term->id();
    $entity_type_id = $this->term->getEntityTypeId();
    // Just being able to create the entity like this verifies a lot of code.
    $entity = EntityTest::create();
    $entity->field_der->target_type = $entity_type_id;
    $entity->field_der->target_id = $tid;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = EntityTest::load($entity->id());
    $this->assertTrue($entity->field_der instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_der[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEquals($entity->field_der->target_id, $tid);
    $this->assertEquals($entity->field_der->target_type, $entity_type_id);
    $this->assertEquals($entity->field_der->entity->getName(), $this->term->getName());
    $this->assertEquals($entity->field_der->entity->id(), $tid);
    $this->assertEquals($entity->field_der->entity->uuid(), $this->term->uuid());

    // Change the name of the term via the reference.
    $new_name = $this->randomMachineName();
    $entity->field_der->entity->setName($new_name);
    $entity->field_der->entity->save();
    // Verify it is the correct name.
    $term = Term::load($tid);
    $this->assertEquals($term->getName(), $new_name);

    // Make sure the computed term reflects updates to the term id.
    $term2 = Term::create(array(
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term2->save();

    // Test all the possible ways of assigning a value.
    $entity->field_der->target_type = $entity_type_id;
    $entity->field_der->target_id = $term->id();
    $this->assertEquals($entity->field_der->entity->id(), $term->id());
    $this->assertEquals($entity->field_der->entity->getName(), $term->getName());

    $entity->field_der = ['target_id' => $term2->id(), 'target_type' => $entity_type_id];
    $this->assertEquals($entity->field_der->entity->id(), $term2->id());
    $this->assertEquals($entity->field_der->entity->getName(), $term2->getName());

    // Test value assignment via the computed 'entity' property.
    $entity->field_der->entity = $term;
    $this->assertEquals($entity->field_der->target_id, $term->id());
    $this->assertEquals($entity->field_der->entity->getName(), $term->getName());

    $entity->field_der->appendItem($term2);
    $this->assertEquals($entity->field_der[1]->target_id, $term2->id());
    $this->assertEquals($entity->field_der[1]->entity->getName(), $term2->getName());

    $entity->field_der = ['entity' => $term2];
    $this->assertEquals($entity->field_der->target_id, $term2->id());
    $this->assertEquals($entity->field_der->entity->getName(), $term2->getName());

    $entity->field_der->appendItem(['entity' => $term]);
    $this->assertEquals($entity->field_der[1]->target_id, $term->id());
    $this->assertEquals($entity->field_der[1]->entity->getName(), $term->getName());

    // Test assigning an invalid item throws an exception.
    try {
      $entity->field_der = [
        'target_id' => $term->id(),
        'target_type' => '',
      ];
      $this->assertTrue(FALSE, 'Assigning an item without target type throws an exception.');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertTrue(TRUE, 'Assigning an item without target type throws an exception.');
    }

    // Test assigning an invalid item throws an exception.
    try {
      $entity->field_der = [
        'target_id' => 'invalid',
        'target_type' => $entity_type_id,
        'entity' => $term2,
      ];
      $this->assertTrue(FALSE, 'Assigning an invalid item throws an exception.');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertTrue(TRUE, 'Assigning an invalid item throws an exception.');
    }

    // Test assigning an invalid item throws an exception.
    try {
      $entity->field_der = [
        'target_id' => $term2->id(),
        'target_type' => 'invalid',
        'entity' => $term2,
      ];
      $this->fail('Assigning an invalid item throws an exception.');
    }
    catch (\InvalidArgumentException $e) {
      $this->pass('Assigning an invalid item throws an exception.');
    }

    $entity->field_der->target_type = $entity_type_id;
    $entity->field_der->target_id = $term2->id();
    $this->assertEquals($entity->field_der->entity->id(), $term2->id());
    $this->assertEquals($entity->field_der->entity->getName(), $term2->getName());

    // Delete terms so we have nothing to reference and try again.
    $term->delete();
    $term2->delete();
    $entity = EntityTest::create(array('name' => $this->randomMachineName()));
    $entity->save();

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->field_der->generateSampleItems();
    $entity->field_der->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests saving order sequence doesn't matter.
   */
  public function testEntitySaveOrder() {
    // The term entity is unsaved here.
    $term = Term::create(array(
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $entity = EntityTest::create();
    // Now assign the unsaved term to the field.
    $entity->field_der->entity = $term;
    $entity->name->value = $this->randomMachineName();
    // Now get the field value.
    $value = $entity->get('field_der');
    $this->assertTrue(empty($value['target_id']));
    $this->assertTrue(!isset($entity->field_der->target_id));
    // And then set it.
    $entity->field_der = $value;
    // Now save the term.
    $term->save();
    // And then the entity.
    $entity->save();
    $this->assertEquals($entity->field_der->entity->id(), $term->id());
  }

  /**
   * Tests entity auto create.
   */
  public function testEntityAutoCreate() {
    // The term entity is unsaved here.
    $term = Term::create(array(
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $entity = EntityTest::create();
    // Now assign the unsaved term to the field.
    $entity->field_der->entity = $term;
    $entity->name->value = $this->randomMachineName();
    // This is equal to storing an entity to tempstore or cache and retrieving
    // it back. An example for this is node preview.
    $entity = serialize($entity);
    $entity = unserialize($entity);
    // And then the entity.
    $entity->save();
    $term = $this->container->get('entity.repository')->loadEntityByUuid($term->getEntityTypeId(), $term->uuid());
    $this->assertEquals($entity->field_der->entity->id(), $term->id());
  }

  /**
   * Tests the der field type for referencing multiple content entities.
   */
  public function testMultipleEntityReferenceItem() {
    // Allow to reference multiple entities.
    $field_storage = FieldStorageConfig::loadByName('entity_test', 'field_der');
    $field_storage->set('settings', array(
      'exclude_entity_types' => FALSE,
      'entity_type_ids' => [
        'taxonomy_term',
        'user',
      ],
    ))->set('cardinality', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->save();
    $entity = EntityTest::create();
    $account = User::load(1);
    $entity->field_der[] = array('entity' => $this->term);
    $entity->field_der[] = array('entity' => $account);
    $entity->save();
    // Check term reference correctly.
    $this->assertEquals($entity->field_der[0]->target_id, $this->term->id());
    $this->assertEquals($entity->field_der[0]->target_type, $this->term->getEntityTypeId());
    $this->assertEquals($entity->field_der[0]->entity->getName(), $this->term->getName());
    $this->assertEquals($entity->field_der[0]->entity->id(), $this->term->id());
    $this->assertEquals($entity->field_der[0]->entity->uuid(), $this->term->uuid());
    // Check user reference correctly.
    $this->assertEquals($entity->field_der[1]->target_id, $account->id());
    $this->assertEquals($entity->field_der[1]->target_type, $account->getEntityTypeId());
    $this->assertEquals($entity->field_der[1]->entity->id(), $account->id());
    $this->assertEquals($entity->field_der[1]->entity->uuid(), $account->uuid());
  }

  /**
   * Tests that the 'handler' field setting stores the proper plugin ID.
   */
  public function testSelectionHandlerSettings() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'dynamic_entity_reference',
      'settings' => array(
        'exclude_entity_types' => FALSE,
        'entity_type_ids' => [
          'entity_test',
        ],
      ),
    ));
    $field_storage->save();

    // Do not specify any value for the 'handler' setting in order to verify
    // that the default value is properly used.
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    $field = FieldConfig::load($field->id());
    $field_settings = $field->getSettings();
    $this->assertTrue($field_settings['entity_test']['handler'] == 'default:entity_test');
    $field_settings['entity_test']['handler'] = 'views';
    $field->setSettings($field_settings);
    $field->save();
    $field = FieldConfig::load($field->id());
    $field_settings = $field->getSettings();
    $this->assertTrue($field_settings['entity_test']['handler'] == 'views');
  }

  /**
   * Tests validation constraint.
   */
  public function testValidation() {
    // The term entity is unsaved here.
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $entity = EntityTest::create([
      'field_der' => [
        'entity' => $term,
        'target_id' => NULL,
        'target_type' => $term->getEntityTypeId(),
      ],
    ]);
    $errors = $entity->validate();
    // Using target_id and target_type of NULL is valid with an unsaved entity.
    $this->assertEquals(0, count($errors));
    // Using target_id of NULL is not valid with a saved entity.
    $term->save();
    $entity = EntityTest::create([
      'field_der' => [
        'entity' => $term,
        'target_id' => NULL,
        'target_type' => $term->getEntityTypeId(),
      ],
    ]);
    $errors = $entity->validate();
    $this->assertEquals(1, count($errors));
    $this->assertEquals($errors[0]->getMessage(), (string) new FormattableMarkup('%property should not be null.', ['%property' => 'target_id']));
    $this->assertEquals($errors[0]->getPropertyPath(), 'field_der.0');
    // This should rectify the issue, favoring the entity over the target_id.
    $entity->save();
    $errors = $entity->validate();
    $this->assertEquals(0, count($errors));
  }

}
