<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext;
use Behat\MinkExtension\Context\RawMinkContext;
use PHPUnit_Framework_Assert as PHPUnit;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
* Defines application features from the specific context.
*/
class PostContext extends RawDrupalContext implements Context, SnippetAcceptingContext {


  /**
   * @AfterScenario @database&&@post
   */
  public function cleanupPost(AfterScenarioScope $scope) {
    $query = \Drupal::entityQuery('post')
      ->condition('field_post', array(
        'This is a public post.',
        'This is a community post.',
        ), 'IN');

    $post_ids = $query->execute();

    $posts = entity_load_multiple('post', $post_ids);

    foreach ($posts as $post) {
      $post->delete();
    }

  }
}
