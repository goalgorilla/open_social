<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines application features related to post.
 */
class PostContext extends RawMinkContext {

  // TODO: Keep track of created posts and clean them up after the fact.

  /**
   * The Drupal mink context is useful for validation of content.
   */
  private MinkContext $minkContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->minkContext = $environment->getContext(SocialMinkContext::class);
  }

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
   * Upload an image on a post form.
   *
   * @When I add image :image to the post form
   */
  public function whenIaddImageToThePostForm(string $image) : void {
    $post_form = $this->getSession()
      ->getPage()
      ->findById("social-post-entity-form");

    if ($post_form === NULL) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), NULL, 'named', array('id', "social-post-entity-form"));
    }

    $uploaded = count($post_form->findAll('css', '.preview'));

    $image_field = $this->getSession()
      ->getPage()
      ->findById("edit-field-post-image-wrapper")
      ?->find('css', "[type='file']");

    if (!$image_field instanceof NodeElement) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'input', 'css', "[type='file']");
    }

    $id = $image_field->getAttribute('id');
    if (!$image_field->isVisible()) {
      $javascript = "document.getElementById('$id').parentNode.classList.remove('hidden')";
      $this->getSession()->executeScript($javascript);
    }

    $this->minkContext->attachFileToField($id, $image);

    // Wait for the number of previews to increase.
    $ajax_timeout = $this->getMinkParameter('ajax_timeout');
    $this->getSession()->getDriver()->wait(1000 * $ajax_timeout, "document.querySelectorAll('#edit-field-post-image-wrapper .preview').length > $uploaded");
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
