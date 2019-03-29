<?php

/**
 * @file
 * Post-update hooks for the Social Font module.
 */

use Drupal\social_font\Entity\Font;

/**
 * Set font-fallback for Montserrat font to sans-serif.
 */
function social_font_post_update_montserrat_fallback() {
  // We assume that the number of fonts installed on a site will never be
  // massive but we can't be sure that Montserrat has id 0 or 1.
  $fonts = Font::loadMultiple();

  /** @var Drupal\social_font\Entity\Font $font */
  foreach ($fonts as $font) {
    if ($font->getName() === "Montserrat") {
      // Set the fallback to use sans-serif.
      $font->set('field_fallback', "1");
      $font->save();
    }
  }
}
