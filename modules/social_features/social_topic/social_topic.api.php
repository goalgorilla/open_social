<?php

/**
 * @file
 * Hooks provided by the Social_topic module.
 */


/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the titles of the topic view.
 *
 * @param string $title
 *   The default generated view title for the term.
 * @param \Drupal\taxonomy\Entity\Term $term
 *   The topic type term that is selected in the filter. NULL if none is selected.
 *
 * @ingroup social_topic_api
 */
function hook_topic_type_title_alter(&$title, &$term) {
  if (isset($term)) {
    $term_title = $term->getName();
    $title = t('@type', ['@type' => $term_title]);
  }
  else {
    $title = t("Newest content");
  }
}

/**
 * @} End of "addtogroup hooks".
 */
