<?php

namespace Drupal\social_topic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Returns responses for Social Topic routes.
 */
class SocialTopicController extends ControllerBase {

  /**
   * The _title_callback for the latest topics view.
   *
   * @return string
   *   The page title.
   */
  public function latestTopicsPageTitle() {
    $title = $this->t('All topics');

    // TODO This might change depending on the view exposed filter settings.
    $topic_type_id = $attributes = \Drupal::request()->query->get('field_topic_type_target_id');
    $term = NULL;
    if ($topic_type_id !== NULL) {
      // Topic type can be "All" will crash overview on /newest-topics.
      if (is_numeric($topic_type_id)) {
        $term = Term::load($topic_type_id);

        if ($term->access('view') && $term->getVocabularyId() === 'topic_types') {
          $term_title = $term->getName();
          $title = $this->t('Topics of type @type', ['@type' => $term_title]);
        }
      }
    }
    // Call hook_topic_type_title_alter().
    \Drupal::moduleHandler()->alter('topic_type_title', $title, $term);

    return $title;
  }

}
