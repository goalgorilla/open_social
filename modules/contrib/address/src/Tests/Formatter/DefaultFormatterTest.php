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
 * Tests the default formatter.
 *
 * @group address
 */
class DefaultFormatterTest extends KernelTestBase {

  /**
   * @var array
   */
  public static $modules = ['system', 'field', 'language', 'text', 'entity_test', 'user', 'address'];

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

    $values = [
      'targetEntityType' => $this->entityType,
      'bundle' => $this->bundle,
      'mode' => 'default',
      'status' => TRUE,
    ];
    $this->display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->create($values);
    $this->display->setComponent($this->fieldName, [
      'type' => 'address_default',
      'settings' => [],
    ]);
    $this->display->save();
  }

  /**
   * Tests Andorra address formatting.
   */
  public function testAndorraAddress() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'AD',
      'locality' => 'AD-07',
      'postal_code' => 'AD500',
      'address_line1' => 'C. Prat de la Creu, 62-64',
    ];

    $this->renderEntityFields($entity, $this->display);
    // Andorra has no predefined administrative areas, but it does have
    // predefined localities, which must be shown.
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">C. Prat de la Creu, 62-64</span><br>' . "\n",
      'line3' => '<span class="postal-code">AD500</span> <span class="locality">Parròquia d&#039;Andorra la Vella</span><br>' . "\n",
      'line4' => '<span class="country">Andorra</span>',
      'line5' => '</p>',
    ]);
    $this->assertRaw($expected, 'The AD address has been properly formatted.');
  }

  /**
   * Tests El Salvador address formatting.
   */
  public function testElSalvadorAddress() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'SV',
      'administrative_area' => 'Ahuachapán',
      'locality' => 'Ahuachapán',
      'address_line1' => 'Some Street 12',
    ];
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">Some Street 12</span><br>' . "\n",
      'line3' => '<span class="locality">Ahuachapán</span><br>' . "\n",
      'line4' => '<span class="administrative-area">Ahuachapán</span><br>' . "\n",
      'line5' => '<span class="country">El Salvador</span>',
      'line6' => '</p>',
    ]);
    $this->assertRaw($expected, 'The SV address has been properly formatted.');

    $entity->{$this->fieldName}->postal_code = 'CP 2101';
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">Some Street 12</span><br>' . "\n",
      'line3' => '<span class="postal-code">CP 2101</span>-<span class="locality">Ahuachapán</span><br>' . "\n",
      'line4' => '<span class="administrative-area">Ahuachapán</span><br>' . "\n",
      'line5' => '<span class="country">El Salvador</span>',
      'line6' => '</p>',
    ]);
    $this->assertRaw($expected, 'The SV address has been properly formatted.');
  }

  /**
   * Tests Taiwan address formatting.
   */
  public function testTaiwanAddress() {
    $language = \Drupal::languageManager()->getLanguage('zh-hant');
    \Drupal::languageManager()->setConfigOverrideLanguage($language);

    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'langcode' => 'zh-hant',
      'country_code' => 'TW',
      'administrative_area' => 'TW-TPE',
      'locality' => 'TW-TPE-e3cc33',
      'address_line1' => 'Sec. 3 Hsin-yi Rd.',
      'postal_code' => '106',
      // Any HTML in the fields is supposed to be escaped.
      'organization' => 'Giant <h2>Bike</h2> Store',
      'recipient' => 'Mr. Liu',
    ];
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="country">台灣</span><br>' . "\n",
      'line3' => '<span class="postal-code">106</span><br>' . "\n",
      'line4' => '<span class="administrative-area">台北市</span><span class="locality">大安區</span><br>' . "\n",
      'line5' => '<span class="address-line1">Sec. 3 Hsin-yi Rd.</span><br>' . "\n",
      'line6' => '<span class="organization">Giant &lt;h2&gt;Bike&lt;/h2&gt; Store</span><br>' . "\n",
      'line7' => '<span class="recipient">Mr. Liu</span>',
      'line8' => '</p>',
    ]);
    $this->assertRaw($expected, 'The TW address has been properly formatted.');
  }

  /**
   * Tests US address formatting.
   */
  public function testUnitedStatesIncompleteAddress() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'US',
      'administrative_area' => 'US-CA',
      'address_line1' => '1098 Alta Ave',
      'postal_code' => '94043',
    ];
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">1098 Alta Ave</span><br>' . "\n",
      'line3' => '<span class="administrative-area">CA</span> <span class="postal-code">94043</span><br>' . "\n",
      'line4' => '<span class="country">United States</span>',
      'line5' => '</p>',
    ]);
    $this->assertRaw($expected, 'The US address has been properly formatted.');

    // Now add the locality, but remove the administrative area.
    $entity->{$this->fieldName}->locality = 'Mountain View';
    $entity->{$this->fieldName}->administrative_area = '';
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">1098 Alta Ave</span><br>' . "\n",
      'line3' => '<span class="locality">Mountain View</span>, <span class="postal-code">94043</span><br>' . "\n",
      'line4' => '<span class="country">United States</span>',
      'line5' => '</p>',
    ]);
    $this->assertRaw($expected, 'The US address has been properly formatted.');
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
