<?php

namespace Drupal\social\Behat;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines application features related to post.
 */
class PostContext extends RawMinkContext {

  // @todo Keep track of created posts and clean them up after the fact.

  /**
   * Select a post visibility.
   *
   * @When I select post visibility :visibility
   */
  public function iSelectPostVisibility($visibility) {
    // First make post visibility setting visible.
    $this->iClickPostVisibilityDropdown();

    // Click the label of the readio button with the visibility. The radio
    // button itself can't be clicked because it's invisible.
    $page = $this->getSession()->getPage();
    $field = $page->findField($visibility);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value|placeholder', $visibility);
    }

    $field->getParent()->click();
  }

  /**
   * Open the post visibility dropdown.
   *
   * @When /^I click the post visibility dropdown/
   */
  public function iClickPostVisibilityDropdown() {
    $locator = 'button#post-visibility';
    $session = $this->getSession();
    $element = $session->getPage()->find('css', $locator);

    if ($element === NULL) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
    }

    // Now click the element.
    $element->click();
  }

  /**
   * Clean up posts created in scenarios.
   *
   * @AfterScenario @database&&@post
   */
  public function cleanupPost(AfterScenarioScope $scope) {
    $query = \Drupal::entityQuery('post')
      ->condition(
        'field_post',
        [
          'This is a public post.',
          'This is a community post.',
        ],
        'IN'
      );

    $post_ids = $query->execute();

    $posts = \Drupal::entityTypeManager()->getStorage('post')->loadMultiple($post_ids);

    foreach ($posts as $post) {
      $post->delete();
    }
  }

}
