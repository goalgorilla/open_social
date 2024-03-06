<?php

declare(strict_types=1);

namespace Drupal\social\Behat\Chrome;

use DMore\ChromeDriver\ChromeDriver as ChromeDriverBase;

/**
 * Contains Open Social specific modifications to the Chrome Driver.
 */
class ChromeDriver extends ChromeDriverBase {

  /**
   * {@inheritdoc}
   */
  public function visit($url) {
    parent::visit($url);
    // We overwrite the visit method because Open Social uses the CKEditor on
    // some pages. The editor waits until the page is loaded similarly to our
    // Behat driver. This can cause a race condition between the CKEditor and
    // our driver, which may cause the driver to calculate a position for an
    // element and have that position change before the driver interacts with it
    // causing random test failures.
    //
    // Ideally we'd put this overwrite in our test contexts but since the test
    // context method exists in RawMinkContext and is used through various
    // routes of inheritance, it's easier to tackle this at the driver level and
    // ensure we actually handle all page visits.
    //
    // To solve the race condition we add a snippet of JavaScript code here that
    // lets us wait for all CKEditors to indicate they're ready if there are any
    // on the page.
    do {
      $attempts = ($attempts ?? 0) + 1;
      $ready = $this->evaluateScript(<<<JS
        // If there's no CKEditor on this page then we're done immediately.
        if (typeof CKEDITOR === "undefined") {
          return true;
        }

        // Find any instance that is not in a state where they're not performing
        // any work.
        for (const instance of Object.values(CKEDITOR.instances)) {
          if (instance.status !== "ready" && instance.status !== "destroyed") {
            return false;
          }
        }

        // If we get here all instances on the page (if any) were in a state
        // where they were no longer making changes to the page.
        return true;
      JS);
      // Keep checking if the instances are ready until they are.
      // We cap the number of attempts to avoid creating an infinite loop.
    } while (!$ready && $attempts <= 100);

    // It's not necessarily a problem if editors aren't ready on the page but
    // we'd rather fail our test and improve the above logic or the page loading
    // than to silently have flakey tests.
    if (!$ready) {
      $path = $this->getCurrentUrl();
      throw new \RuntimeException("Found CKEditor instances on the page ($path) but they were not ready sufficiently quickly after page load which could cause tests to randomly fail in future steps, aborting.");
    }
  }

}
