<?php

namespace Drupal\dynamic_entity_reference\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\Gettext;

/**
 * Tests localization aspects of the module.
 *
 * @group dynamic_entity_reference
 */
class DynamicEntityReferenceLocaleTest extends DynamicEntityReferenceTest {

  public static $modules = [
    'language',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $file = new \stdClass();
    $file->uri = \Drupal::service('file_system')->realpath(drupal_get_path('module', 'dynamic_entity_reference') . '/tests/test.de.po');
    $file->langcode = 'de';
    Gettext::fileToDatabase($file, array());

    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->config('system.site')->set('default_langcode', 'de')->save();
    // Rebuild the container to update the default language container variable.
    $this->rebuildContainer();
  }

}
