<?php

/**
 * @file
 * Contains \Drupal\Tests\token\Kernel\TaxonomyTest.
 */

namespace Drupal\Tests\token\Kernel;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests taxonomy tokens.
 *
 * @group token
 */
class TaxonomyTest extends KernelTestBase {
  protected $vocab;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'text');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    // Create the default tags vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();
    $this->vocab = $vocabulary;
  }

  /**
   * Test the additional taxonomy term tokens.
   */
  function testTaxonomyTokens() {
    $root_term = $this->addTerm($this->vocab, array('name' => 'Root term', 'path' => array('alias' => '/root-term')));
    $tokens = array(
      'url' => \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $root_term->id()], array('absolute' => TRUE)),
      'url:absolute' => \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $root_term->id()], array('absolute' => TRUE)),
      'url:relative' => \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $root_term->id()], array('absolute' => FALSE)),
      'url:path' => '/root-term',
      'url:unaliased:path' => "/taxonomy/term/{$root_term->id()}",
      'edit-url' => \Drupal::url('entity.taxonomy_term.edit_form', ['taxonomy_term' => $root_term->id()], array('absolute' => TRUE)),
      'parents' => NULL,
      'parents:count' => NULL,
      'parents:keys' => NULL,
      'root' => NULL,
      // Deprecated tokens
      'url:alias' => '/root-term',
    );
    $this->assertTokens('term', array('term' => $root_term), $tokens);

    $parent_term = $this->addTerm($this->vocab, array('name' => 'Parent term', 'parent' => $root_term->id()));
    $tokens = array(
      'url' => \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $parent_term->id()], array('absolute' => TRUE)),
      'url:absolute' => \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $parent_term->id()], array('absolute' => TRUE)),
      'url:relative' => \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $parent_term->id()], array('absolute' => FALSE)),
      'url:path' => "/taxonomy/term/{$parent_term->id()}",
      'url:unaliased:path' => "/taxonomy/term/{$parent_term->id()}",
      'edit-url' => \Drupal::url('entity.taxonomy_term.edit_form', ['taxonomy_term' => $parent_term->id()], array('absolute' => TRUE)),
      'parents' => 'Root term',
      'parents:count' => 1,
      'parents:keys' => $root_term->id(),
      'root' => $root_term->label(),
      'root:tid' => $root_term->id(),
      // Deprecated tokens
      'url:alias' => "/taxonomy/term/{$parent_term->id()}",
    );
    $this->assertTokens('term', array('term' => $parent_term), $tokens);

    $term = $this->addTerm($this->vocab, array('name' => 'Test term', 'parent' => $parent_term->id()));
    $tokens = array(
      'parents' => 'Root term, Parent term',
      'parents:count' => 2,
      'parents:keys' => implode(', ', array($root_term->id(), $parent_term->id())),
    );
    $this->assertTokens('term', array('term' => $term), $tokens);
  }

  /**
   * Test the additional vocabulary tokens.
   */
  function testVocabularyTokens() {
    $vocabulary = $this->vocab;
    $tokens = array(
      'machine-name' => 'tags',
      'edit-url' => \Drupal::url('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()], array('absolute' => TRUE)),
    );
    $this->assertTokens('vocabulary', array('vocabulary' => $vocabulary), $tokens);
  }

  function addVocabulary(array $vocabulary = array()) {
    $vocabulary += array(
      'name' => Unicode::strtolower($this->randomMachineName(5)),
      'nodes' => array('article' => 'article'),
    );
    $vocabulary = entity_create('taxonomy_vocabulary', $vocabulary)->save();
    return $vocabulary;
  }

  function addTerm($vocabulary, array $term = array()) {
    $term += array(
      'name' => Unicode::strtolower($this->randomMachineName(5)),
      'vid' => $vocabulary->id(),
    );
    $term = entity_create('taxonomy_term', $term);
    $term->save();
    return $term;
  }
}
