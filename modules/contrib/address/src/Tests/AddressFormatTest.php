<?php

namespace Drupal\address\Tests;

use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use Drupal\address\Entity\AddressFormat;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Tests the address format entity and UI.
 *
 * @group address
 */
class AddressFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'address',
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

    $this->adminUser = $this->drupalCreateUser([
      'administer address formats',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test importing address formats using service.
   */
  function testAddressFormatImport() {
    $external_repository = new AddressFormatRepository();
    $external_count = count($external_repository->getAll());
    $count = \Drupal::entityQuery('address_format')->count()->execute();
    $this->assertEqual($external_count, $count, 'All address formats imported at installation.');
  }

  /**
   * Tests creating a address format via a form and programmatically.
   */
  function testAddressFormatCreation() {
    $country_code = 'CM';
    $values = [
      'countryCode' => $country_code,
      'format' => '%locality',
      'localityType' => 'city',
    ];
    $address_format = AddressFormat::create($values);
    $address_format->save();
    $this->drupalGet('admin/config/regional/address-formats/manage/' . $address_format->id());
    $this->assertResponse(200, 'The new address format can be accessed at admin/config/regional/address-formats.');

    $address_format = AddressFormat::load($country_code);
    $this->assertEqual($address_format->getCountryCode(), $values['countryCode'], 'The new address format has the correct countryCode.');
    $this->assertEqual($address_format->getFormat(), $values['format'], 'The new address format has the correct format string.');
    $this->assertEqual($address_format->getLocalityType(), $values['localityType'], 'The new address format has the correct localityType.');

    $country_code = 'YE';
    $edit = [
      'countryCode' => $country_code,
      'format' => '%locality',
      'localityType' => 'city',
    ];
    $this->drupalGet('admin/config/regional/address-formats/add');
    $this->assertResponse(200, 'The address format add form can be accessed at admin/config/regional/address-formats/add.');
    $this->drupalPostForm('admin/config/regional/address-formats/add', $edit, t('Save'));

    $address_format = AddressFormat::load($country_code);
    $this->assertEqual($address_format->getCountryCode(), $edit['countryCode'], 'The new address format has the correct countryCode.');
    $this->assertEqual($address_format->getFormat(), $edit['format'], 'The new address format has the correct format string.');
    $this->assertEqual($address_format->getLocalityType(), $edit['localityType'], 'The new address format has the correct localityType.');
  }

  /**
   * Tests editing a address format via a form.
   */
  function testAddressFormatEditing() {
    $country_code = 'RS';
    $address_format = AddressFormat::load($country_code);
    $new_postal_code_type = ($address_format->getPostalCodeType() == 'zip') ? 'postal' : 'zip';
    $edit = [
      'postalCodeType' => $new_postal_code_type,
    ];
    $this->drupalPostForm('admin/config/regional/address-formats/manage/' . $country_code, $edit, t('Save'));

    $address_format = AddressFormat::load($country_code);
    $this->assertEqual($address_format->getPostalCodeType(), $new_postal_code_type, 'The address format PostalCodeType has been changed.');
  }

  /**
   * Tests deleting a address format via a form.
   */
  public function testAddressFormatDeletion() {
    $country_code = 'RS';
    $this->drupalGet('admin/config/regional/address-formats/manage/' . $country_code . '/delete');
    $this->assertResponse(200, 'The address format delete form can be accessed.');
    $this->assertText(t('This action cannot be undone.'), 'The address format delete confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    $address_formatExists = (bool) AddressFormat::load($country_code);
    $this->assertFalse($address_formatExists, 'The address format has been deleted form the database.');
  }

  /**
   * Tests deleting a address format for countryCode = ZZ via a form and from the API.
   */
  function testAddressFormatDeleteZZ() {
    $country_code = 'ZZ';
    $this->drupalGet('admin/config/regional/address-formats/manage/' . $country_code . '/delete');
    $this->assertResponse(403, "The delete form for the 'ZZ' address format cannot be accessed.");
    // Try deleting ZZ from the API
    $address_format = AddressFormat::load($country_code);
    try {
      $address_format->delete();
      $this->fail("The 'ZZ' address format can't be deleted.");
    }
    catch (EntityStorageException $e) {
      $this->assertEqual("The 'ZZ' address format can't be deleted.", $e->getMessage());
    }
  }

}
