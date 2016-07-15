<?php

namespace Drupal\address\Tests\Formatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the plain formatter.
 *
 * @group address
 */
class PlainFormatterTest extends KernelTestBase {

  /**
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'language',
    'text',
    'entity_test',
    'user',
    'address'
  ];

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['text']);
    $this->installConfig(['address']);
    $this->installEntitySchema('entity_test');

    ConfigurableLanguage::createFromLangcode('zh-hant')->save();

    // The address module is never installed, so the importer doesn't run
    // automatically. Instead, we manually import the address formats we need.
    $country_codes = ['AD', 'SV', 'TW', 'US', 'ZZ'];
    $importer = \Drupal::service('address.address_format_importer');
    $importer->importEntities($country_codes);
    $importer->importTranslations(['zh-hant']);

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = Unicode::strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'address',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $field->save();

    $this->display = entity_get_display($this->entityType, $this->bundle, 'default');
    $this->display->setComponent($this->fieldName, [
      'type' => 'address_plain',
      'settings' => [],
    ]);
    $this->display->save();
  }

  /**
   * Tests the rendered output.
   */
  public function testRender() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'AD',
      'locality' => 'AD-07',
      'postal_code' => 'AD500',
      'address_line1' => 'C. Prat de la Creu, 62-64',
    ];
    $this->renderEntityFields($entity, $this->display);

    // Confirm the expected elements, including the predefined locality
    // (properly escaped), country name.
    $expected_elements = [
      'C. Prat de la Creu, 62-64',
      'AD500',
      'Parròquia d&#039;Andorra la Vella',
      'Andorra',
    ];
    foreach ($expected_elements as $expected_element) {
      $this->assertRaw($expected_element);
    }

    // Confirm that an unrecognized locality is shown unmodified.
    $entity->{$this->fieldName}->locality = 'FAKE_LOCALITY';
    $this->renderEntityFields($entity, $this->display);
    $this->assertRaw('FAKE_LOCALITY');
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

}
