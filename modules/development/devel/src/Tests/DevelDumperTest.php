<?php

/**
 * @file
 * Tests for devel module.
 */

namespace Drupal\devel\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Tests pluggable dumper feature.
 *
 * @group devel
 */
class DevelDumperTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['devel', 'devel_dumper_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(['administer site configuration', 'access devel information']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test dumpers configuration page.
   */
  public function testDumpersConfiguration() {
    $this->drupalGet('admin/config/development/devel');

    // Ensures that the dumper input is present on the config page.
    $this->assertFieldByName('dumper');

    // Ensures that the 'default' dumper is enabled by default.
    $this->assertFieldChecked('edit-dumper-default');

    // Ensures that all dumpers declared by devel are present on the config page
    // and that only the available dumpers are selectable.
    $dumpers = ['default', 'drupal_variable', 'firephp', 'chromephp'];
    $available_dumpers = ['default', 'drupal_variable'];

    foreach ($dumpers as $dumper) {
      $this->assertFieldByXPath('//input[@type="radio" and @name="dumper"]', $dumper, new FormattableMarkup('Radio button for @dumper found.', ['@dumper' => $dumper]));
      if (in_array($dumper, $available_dumpers)) {
        $this->assertFieldByXPath('//input[@name="dumper" and not(@disabled="disabled")]', $dumper, new FormattableMarkup('Dumper @dumper is available.', ['@dumper' => $dumper]));
      }
      else {
        $this->assertFieldByXPath('//input[@name="dumper" and @disabled="disabled"]', $dumper, new FormattableMarkup('Dumper @dumper is disabled.', ['@dumper' => $dumper]));
      }
    }

    // Ensures that dumper plugins declared by other modules are present on the
    // config page and that only the available dumpers are selectable.
    $this->assertFieldByName('dumper', 'available_test_dumper');
    $this->assertText('Available test dumper.', 'Available dumper label is present');
    $this->assertText('Drupal dumper for testing purposes (available).', 'Available dumper description is present');
    $this->assertFieldByXPath('//input[@name="dumper" and not(@disabled="disabled")]', 'available_test_dumper', 'Available dumper input not is disabled.');

    $this->assertFieldByName('dumper', 'not_available_test_dumper');
    $this->assertText('Not available test dumper.', 'Non available dumper label is present');
    $this->assertText('Drupal dumper for testing purposes (not available).Not available. You may need to install external dependencies for use this plugin.', 'Non available dumper description is present');
    $this->assertFieldByXPath('//input[@name="dumper" and @disabled="disabled"]', 'not_available_test_dumper', 'Non available dumper input is disabled.');

    // Ensures that saving of the dumpers configuration works as expected.
    $edit = [
      'dumper' => 'drupal_variable',
    ];
    $this->drupalPostForm('admin/config/development/devel', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $config = \Drupal::config('devel.settings')->get('devel_dumper');
    $this->assertEqual('drupal_variable', $config, 'The configuration options have been properly saved');

    // Ensure that if the chosen dumper is not available (e.g. the module that
    // provide it is uninstalled) the 'default' dumper appears selected in the
    // config page.
    \Drupal::service('module_installer')->install(['kint']);

    $this->drupalGet('admin/config/development/devel');
    $this->assertFieldByName('dumper', 'kint');

    $edit = [
      'dumper' => 'kint',
    ];
    $this->drupalPostForm('admin/config/development/devel', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $config = \Drupal::config('devel.settings')->get('devel_dumper');
    $this->assertEqual('kint', $config, 'The configuration options have been properly saved');

    \Drupal::service('module_installer')->uninstall(['kint']);

    $this->drupalGet('admin/config/development/devel');
    $this->assertNoFieldByName('dumper', 'kint');
    $this->assertFieldChecked('edit-dumper-default');
  }

  /**
   * Test variable is dumped in page.
   */
  function testDumpersOutput() {
    $edit = [
      'dumper' => 'available_test_dumper',
    ];
    $this->drupalPostForm('admin/config/development/devel', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->drupalGet('devel_dumper_test/dump');
    $elements = $this->xpath('//body/pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::dump() Test output']);
    $this->assertTrue(!empty($elements), 'Dumped message is present.');

    $this->drupalGet('devel_dumper_test/message');
    $elements = $this->xpath('//div[contains(@class, "messages")]/pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::export() Test output']);
    $this->assertTrue(!empty($elements), 'Dumped message is present.');

    $this->drupalGet('devel_dumper_test/export');
    $elements = $this->xpath('//div[@class="layout-content"]//pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::export() Test output']);
    $this->assertTrue(!empty($elements), 'Dumped message is present.');

    $this->drupalGet('devel_dumper_test/export_renderable');
    $elements = $this->xpath('//div[@class="layout-content"]//pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::exportAsRenderable() Test output']);
    $this->assertTrue(!empty($elements), 'Dumped message is present.');
    // Ensures that plugins can add libraries to the page when the
    // ::exportAsRenderable() method is used.
    $this->assertRaw('devel_dumper_test/css/devel_dumper_test.css');
    $this->assertRaw('devel_dumper_test/js/devel_dumper_test.js');

    $debug_filename = file_directory_temp() . '/drupal_debug.txt';

    $this->drupalGet('devel_dumper_test/debug');
    $file_content = file_get_contents($debug_filename);
    $expected = <<<EOF
<pre>AvailableTestDumper::export() Test output</pre>

EOF;
    $this->assertEqual($file_content, $expected, 'Dumped message is present.');

    // Ensures that the DevelDumperManager::debug() is not access checked and
    // that the dump is written in the debug file even if the user has not the
    // 'access devel information' permission.
    file_put_contents($debug_filename, '');
    $this->drupalLogout();
    $this->drupalGet('devel_dumper_test/debug');
    $file_content = file_get_contents($debug_filename);
    $expected = <<<EOF
<pre>AvailableTestDumper::export() Test output</pre>

EOF;
    $this->assertEqual($file_content, $expected, 'Dumped message is present.');
  }

}
