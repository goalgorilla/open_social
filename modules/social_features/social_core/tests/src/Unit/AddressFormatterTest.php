<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Unit;

use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;
use Drupal\address\Repository\CountryRepository;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\social_core\Plugin\Field\FieldFormatter\AddressFormatter;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Field Formatter for the link field type.
 *
 * @group link
 */
class AddressFormatterTest extends UnitTestCase {

  /**
   * Create mock and call view elements from Address Formatter plugin.
   *
   * @param array $listValue
   *   Address list to mock.
   *
   * @return array
   *   The form-element with address.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  private function getViewElements(array $listValue): array {
    $addressItem = $this->createMock(AddressFieldItemList::class);
    $addressItem->expects($this->any())
      ->method('getValue')
      ->willReturn($listValue);

    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $addressList = new AddressFieldItemList($fieldDefinition, '', $addressItem);

    $fieldTypePluginManager = $this->createMock(FieldTypePluginManagerInterface::class);
    $fieldTypePluginManager->expects($this->once())
      ->method('createFieldItem')
      ->willReturn($addressItem);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturn([
        'format' => '@address_line1%, @address_line2%, @address_line3%, @postal_code% @locality%, @country_code%',
        'regex_pattern' => '/@[\s\S]+?%, |@[\s\S]+?% |@[\s\S]+?% - /',
      ]);
    $config_factory->method('get')->willReturnMap([
      ['social_core.address.settings', $config],
    ]);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $fieldTypePluginManager);
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);

    $addressList->setValue([$addressItem]);

    $cacheBackend = $this->createMock(CacheBackendInterface::class);
    $langManager = $this->createMock(LanguageManagerInterface::class);
    $langManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $countryRepository = new CountryRepository($cacheBackend, $langManager);

    $address_formatter = new AddressFormatter('', [], $fieldDefinition, [], '', '', [], $countryRepository, $config_factory);
    return $address_formatter->viewElements($addressList, 'en');
  }

  /**
   * Test with full-address (street, postal-code, state, city and country).
   */
  public function testFullAddressFormatter(): void {
    $address_list = [
      'langcode' => NULL,
      'country_code' => 'US',
      'administrative_area' => 'Michigan',
      'locality' => 'Portage',
      'dependent_locality' => NULL,
      'postal_code' => '12345-1234',
      'sorting_code' => NULL,
      'address_line1' => '1234 Main Street',
      'address_line2' => NULL,
      'address_line3' => NULL,
      'organization' => NULL,
      'given_name' => NULL,
      'additional_name' => NULL,
      'family_name' => NULL,
    ];

    $address_element = $this->getViewElements($address_list);

    $this->assertEquals(['#markup' => '1234 Main Street, 12345-1234 Portage, United States'], $address_element);
  }

  /**
   * Test with partial address (postal-code, city and country)
   */
  public function testPartialAddressFormatter(): void {
    $address_list = [
      'langcode' => NULL,
      'country_code' => 'US',
      'administrative_area' => NULL,
      'locality' => 'Portage',
      'dependent_locality' => NULL,
      'postal_code' => '12345-1234',
      'sorting_code' => NULL,
      'address_line1' => NULL,
      'address_line2' => NULL,
      'address_line3' => NULL,
      'organization' => NULL,
      'given_name' => NULL,
      'additional_name' => NULL,
      'family_name' => NULL,
    ];

    $address_element = $this->getViewElements($address_list);

    $this->assertEquals(['#markup' => '12345-1234 Portage, United States'], $address_element);
  }

  /**
   * Test with empty address field.
   */
  public function testAddressFormatterWithoutEmptyAddress(): void {
    $address_list = [];

    $address_element = $this->getViewElements($address_list);

    $this->assertEmpty($address_element);
  }

}
