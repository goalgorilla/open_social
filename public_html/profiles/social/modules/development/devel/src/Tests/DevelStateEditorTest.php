<?php

/**
 * @file
 * Contains \Drupal\devel\Tests\DevelStateEditorTest.
 */

namespace Drupal\devel\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Tests devel state editor.
 *
 * @group devel
 */
class DevelStateEditorTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['devel'];

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $develUser;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->state = $this->container->get('state');

    $this->develUser = $this->drupalCreateUser(['access devel information']);
    $this->adminUser = $this->drupalCreateUser(['access devel information', 'administer site configuration']);
  }

  /**
   * Tests state listing.
   */
  public function testStateListing() {
    // Ensure that state listing page is accessible only by users with the
    // adequate permissions.
    $this->drupalGet('devel/state');
    $this->assertResponse(403);

    $this->drupalLogin($this->develUser);
    $this->drupalGet('devel/state');
    $this->assertResponse(200);
    $this->assertText(t('State editor'));

    // Ensure that the state variables table is visible.
    $table = $this->xpath('//table[contains(@class, "devel-state-list")]');
    $this->assertTrue($table, 'State list table found.');

    // Ensure that all state variables are listed in the table.
    $states = \Drupal::keyValue('state')->getAll();
    $rows = $this->xpath('//table[contains(@class, "devel-state-list")]//tbody//tr');
    $this->assertEqual(count($rows), count($states), 'All states are listed in the table.');

    // Ensure that the added state variables are listed in the table.
    $this->state->set('devel.simple', 'Hello!');
    $this->drupalGet('devel/state');
    $this->assertFieldByXpath('//table[contains(@class, "devel-state-list")]//tbody//td', 'devel.simple', 'Label found for the added state.');

    $thead_xpath = '//table[contains(@class, "devel-state-list")]/thead/tr/th';
    $action_xpath = '//table[contains(@class, "devel-state-list")]//ul[@class="dropbutton"]/li/a';

    // Ensure that the operations column and the actions buttons are not
    // available for user without 'administer site configuration' permission.
    $elements = $this->xpath($thead_xpath);
    $this->assertEqual(count($elements), 2, 'Correct number of table header cells found.');

    $expected_items = ['Name', 'Value'];
    foreach ($elements as $key => $element) {
      $this->assertIdentical((string) $element[0], $expected_items[$key]);
    }

    $this->assertFalse($this->xpath($action_xpath), 'Action buttons are not visible.');

    // Ensure that the operations column and the actions buttons are
    // available for user with 'administer site configuration' permission.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('devel/state');

    $elements = $this->xpath($thead_xpath);
    $this->assertEqual(count($elements), 3, 'Correct number of table header cells found.');

    $expected_items = ['Name', 'Value', 'Operations'];
    foreach ($elements as $key => $element) {
      $this->assertIdentical((string) $element[0], $expected_items[$key]);
    }

    $this->assertTrue($this->xpath($action_xpath), 'Action buttons are visible.');

    // Test that the edit button works properly.
    $this->clickLink(t('Edit'));
    $this->assertResponse(200);
  }

  /**
   * Tests state edit.
   */
  public function testStateEdit() {
    // Create some state variables for the test.
    $this->state->set('devel.simple', 0);
    $this->state->set('devel.array', ['devel' => 'value']);
    $this->state->set('devel.object', $this->randomObject());

    // Ensure that state edit form is accessible only by users with the
    // adequate permissions.
    $this->drupalLogin($this->develUser);
    $this->drupalGet('devel/state/edit/devel.simple');
    $this->assertResponse(403);

    $this->drupalLogin($this->adminUser);

    // Ensure that accessing an un-existent state variable cause a warning
    // message.
    $this->drupalGet('devel/state/edit/devel.unknown');
    $this->assertText(t('State @name does not exist in the system.', ['@name' => 'devel.unknown']));

    // Ensure that state variables that contain simple type can be edited and
    // saved.
    $this->drupalGet('devel/state/edit/devel.simple');
    $this->assertResponse(200);
    $this->assertText(t('Edit state variable: @name', ['@name' => 'devel.simple']));
    $this->assertInputNotDisabledById('edit-new-value');
    $this->assertInputNotDisabledById('edit-submit');

    $edit = ['new_value' => 1];
    $this->drupalPostForm('devel/state/edit/devel.simple', $edit, t('Save'));
    $this->assertText(t('Variable @name was successfully edited.', ['@name' => 'devel.simple']));
    $this->assertEqual(1, $this->state->get('devel.simple'));

    // Ensure that state variables that contain array can be edited and saved
    // and the new value is properly validated.
    $this->drupalGet('devel/state/edit/devel.array');
    $this->assertResponse(200);
    $this->assertText(t('Edit state variable: @name', ['@name' => 'devel.array']));
    $this->assertInputNotDisabledById('edit-new-value');
    $this->assertInputNotDisabledById('edit-submit');

    // Try to save an invalid yaml input.
    $edit = ['new_value' => 'devel: \'value updated'];
    $this->drupalPostForm('devel/state/edit/devel.array', $edit, t('Save'));
    $this->assertText(t('Invalid input:'));

    $edit = ['new_value' => 'devel: \'value updated\''];
    $this->drupalPostForm('devel/state/edit/devel.array', $edit, t('Save'));
    $this->assertText(t('Variable @name was successfully edited.', ['@name' => 'devel.array']));
    $this->assertEqual(['devel' => 'value updated'], $this->state->get('devel.array'));

    // Ensure that state variables that contain objects cannot be edited.
    $this->drupalGet('devel/state/edit/devel.object');
    $this->assertResponse(200);
    $this->assertText(t('Edit state variable: @name', ['@name' => 'devel.object']));
    $this->assertText(t('Only simple structures are allowed to be edited. State @name contains objects.', ['@name' => 'devel.object']));
    $this->assertInputDisabledById('edit-new-value');
    $this->assertInputDisabledById('edit-submit');

    // Ensure that the cancel link works as expected.
    $this->clickLink(t('Cancel'));
    $this->assertUrl('devel/state');
  }

  /**
   * Helper function for check if an input is disabled.
   *
   * @param string $id
   *   The ID of the input.
   */
  protected function assertInputDisabledById($id) {
    $message = new FormattableMarkup('The input %id is disabled.', ['%id' => $id]);
    $xpath = '//textarea[@id=:id and @disabled="disabled"]|//input[@id=:id and @disabled="disabled"]|//select[@id=:id and @disabled="disabled"]';
    $query = $this->buildXPathQuery($xpath, [':id' => $id]);
    $this->assertFieldByXPath($query, NULL, $message);
  }

  /**
   * Helper function for check if an input is not disabled.
   *
   * @param string $id
   *   The ID of the input.
   */
  protected function assertInputNotDisabledById($id) {
    $message = new FormattableMarkup('The input %id is not disabled.', ['%id' => $id]);
    $xpath = '//textarea[@id=:id and not(@disabled="disabled")]|//input[@id=:id and not(@disabled="disabled")]|//select[@id=:id and not(@disabled="disabled")]';
    $query = $this->buildXPathQuery($xpath, [':id' => $id]);
    $this->assertFieldByXPath($query, NULL, $message);
  }

}
