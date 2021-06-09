<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
* Defines application features from the specific context.
*/
class PostContext extends RawDrupalContext implements Context {


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

    $posts = \Drupal::entityTypeManager()->getStorage('post')->loadMultiple($post_ids);

    foreach ($posts as $post) {
      $post->delete();
    }

  }
}
