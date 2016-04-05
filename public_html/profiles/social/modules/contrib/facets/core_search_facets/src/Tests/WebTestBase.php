<?php

namespace Drupal\core_search_facets\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase as SimpletestWebTestBase;

/**
 * Provides the base class for web tests for Core Search Facets.
 */
abstract class WebTestBase extends SimpletestWebTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'field',
    'search',
    'node',
    'facets',
    'block',
    'core_search_facets',
    'language',
  ];

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user without Search / Facet admin permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * The anonymous user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymousUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create content types.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateContentType(['type' => 'article']);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Make the body field translatable. The title is already translatable by
    // definition. The parent class has already created the article and page
    // content types.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();

    // Adding 10 pages.
    for ($i = 1; $i <= 9; $i++) {
      // Adding a different created time per language to avoid to have exactly
      // the same value per nid and langcode.
      $created_time_en = new \DateTime('February ' . $i . ' 2016 ' . str_pad($i, 2, STR_PAD_LEFT, 0) . 'PM');
      $created_time_es = new \DateTime('March ' . $i . ' 2016 ' . str_pad($i, 2, STR_PAD_LEFT, 0) . 'PM');
      $node = $this->drupalCreateNode(array(
        'title' => 'test page' . $i . ' EN',
        'body' => 'test page' . $i,
        'type' => 'page',
        'created' => $created_time_en->format('U'),
        'langcode' => 'en',
      ));

      // Add Spanish translation to the node.
      $node->addTranslation('es', [
        'title' => 'test page' . $i . ' ES',
        'created' => $created_time_es->format('U'),
      ]);
      $node->save();

    }

    $created_time = new \DateTime('March 9 2016 11PM');
    $this->drupalCreateNode(array(
      'title' => 'test page 10 EN',
      'body' => 'test page10',
      'type' => 'page',
      'created' => $created_time->format('U'),
      'langcode' => 'en',
    ));

    // Adding 10 articles.
    for ($i = 1; $i <= 10; $i++) {
      $created_time = new \DateTime('April ' . $i . ' 2016 ' . str_pad($i, 2, STR_PAD_LEFT, 0) . 'PM');
      $this->drupalCreateNode(array(
        'title' => 'test article' . $i . ' EN',
        'body' => 'test article' . $i,
        'type' => 'article',
        'created' => $created_time->format('U'),
        'langcode' => 'en',
      ));
    }

    // Create the users used for the tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer search',
      'use advanced search',
      'administer facets',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
      'search content',
      'administer languages',
      'administer site configuration',
      'access content',
    ]);
  }

}
