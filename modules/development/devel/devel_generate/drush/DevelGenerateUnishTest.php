<?php

/**
 * @file
 * Contains \Unish\DevelGenerateUnishTest.
 */

namespace Unish;

if (class_exists('Unish\CommandUnishTestCase')) {

  /**
   * Tests for devel_generate drush commands.
   *
   * @group devel_generate
   */
  class DevelGenerateUnishTest extends CommandUnishTestCase {

    /**
     * {@inheritdoc}
     */
    public function setUp() {
      if (UNISH_DRUPAL_MAJOR_VERSION < 8) {
        $this->markTestSkipped('Devel Generate Tests only available on D8+.');
      }

      if (!$this->getSites()) {
        $this->setUpDrupal(1, TRUE, UNISH_DRUPAL_MAJOR_VERSION, 'standard');

        // Symlink the devel module into the sandbox.
        $devel_directory = dirname(dirname(__DIR__));
        symlink($devel_directory, $this->webroot() . '/modules/devel');

        // Enable the devel_generate modules.
        $this->drush('pm-enable', ['devel_generate'], $this->getOptions());
      }

    }

    /**
     * Tests devel generate terms.
     */
    public function testDevelGenerateTerms() {
      $this->drush('pm-enable', ['taxonomy'], $this->getOptions());

      $this->drush('generate-terms', [], $this->getOptions(), NULL, NULL, static::EXIT_ERROR);
      $this->assertContains('Please provide a vocabulary machine name.', $this->getErrorOutput());

      $this->drush('generate-terms', ['unknown'], $this->getOptions(), NULL, NULL, static::EXIT_ERROR);
      $this->assertContains('Invalid vocabulary name: unknown', $this->getErrorOutput());

      $this->drush('generate-terms', ['tags', 'NaN'], $this->getOptions(), NULL, NULL, static::EXIT_ERROR);
      $this->assertContains('Invalid number of terms: NaN', $this->getErrorOutput());

      $eval_term_count = "return \\Drupal::entityQuery('taxonomy_term')->count()->execute();";
      $eval_options = $this->getOptions() + ['format' => 'string'];

      $this->drush('generate-terms', ['tags'], $this->getOptions());
      $this->assertContains('Created the following new terms:', $this->getErrorOutput());
      $this->drush('php-eval', [$eval_term_count], $eval_options);
      $this->assertEquals(10, $this->getOutput());

      $this->drush('generate-terms', ['tags', '1'], $this->getOptions());
      $this->assertContains('Created the following new terms:', $this->getErrorOutput());
      $this->drush('php-eval', [$eval_term_count], $eval_options);
      $this->assertEquals(11, $this->getOutput());

      $this->drush('generate-terms', ['tags', '1'], $this->getOptions(TRUE));
      $this->assertContains('Deleted existing terms.', $this->getErrorOutput());
      $this->assertContains('Created the following new terms:', $this->getErrorOutput());
      $this->drush('php-eval', [$eval_term_count], $eval_options);
      $this->assertEquals(1, $this->getOutput());

      $this->drush('gent', ['tags', '1'], $this->getOptions());
      $this->assertContains('Created the following new terms:', $this->getErrorOutput());
    }

    /**
     * Default drush options.
     *
     * @param bool $kill
     *   Whether add kill option.
     *
     * @return array
     *   An array containing the default options for drush commands.
     */
    protected function getOptions($kill = FALSE) {
      $options = [
        'yes' => NULL,
        'root' => $this->webroot(),
        'uri' => key($this->getSites()),
      ];

      if($kill) {
        $options['kill'] = NULL;
      }

      return $options;
    }

  }

}
