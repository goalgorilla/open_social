<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines application features related to comments.
 */
class CommentContext extends RawMinkContext {

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
   * Upload an file on a post form.
   *
   * @When I add file :file to the comment form
   */
  public function whenIaddFileToThePostForm(string $file) : void {
    $this->minkContext->pressButton("Add attachment");

    $page = $this->getSession()->getPage();

    $uploaded = count($page->findAll('css', '.comment .file'));

    // @todo This doesn't have a label which is a problem.
    $file_field = $page->find('css', '.comment [type=file]');
    $this->minkContext->attachFileToField($file_field->getAttribute('id'), $file);

    // Wait for the number of previews to increase.
    $ajax_timeout = $this->getMinkParameter('ajax_timeout');
    if (!$this->getSession()->getDriver()->wait(1000 * $ajax_timeout, "document.querySelectorAll('.comment .file').length > $uploaded")) {
      throw new \Exception("Could not add file to post form: file preview was not rendered after $ajax_timeout seconds.");
    }
  }

}
