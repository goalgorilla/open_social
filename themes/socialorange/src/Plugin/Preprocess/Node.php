<?php

namespace Drupal\socialorange\Plugin\Preprocess;

use Drupal\socialbase\Plugin\Preprocess\Node as NodeBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Pre-processes variables for the "node" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("node")
 */
class Node extends NodeBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);
    /* @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];

    // Add topic/event type or content type label.
    $node_tag = $node->getType() . '_type';
    $variables['content_type'] = !empty($variables[$node_tag]) ? $variables[$node_tag] : ucfirst($node->getType());

    switch ($node->getType()) {
      case 'topic':
        $content_type = $this->t('Content');
        $url = Url::fromRoute('view.latest_topics.page_latest_topics');
        $node_type = Link::fromTextAndUrl($content_type, $url);
        break;

      case 'event':
        $content_type = $this->t('Event');
        $url = Url::fromRoute('view.upcoming_events.page_community_events');
        $node_type = Link::fromTextAndUrl($content_type, $url);

        // This will get the users timezone, which is either set by the user
        // or defaults back to the sites timezone if the user didn't select any.
        $timezone = drupal_get_user_timezone();
        // Timezone that dates should be stored in.
        $utc_timezone = DateTimeItemInterface::STORAGE_TIMEZONE;
        // Get start date.
        if ($start_date_field = $node->field_event_date) {
          if (!empty($start_date_field->value)) {
            $start_datetime = new \DateTime($start_date_field->value, new \DateTimeZone($utc_timezone));
            $start_datetime->setTimezone(new \DateTimeZone($timezone));
            $start_datetime = $start_datetime->getTimestamp();
            $date_formatter = \Drupal::service('date.formatter');
            $variables['start_month'] = $date_formatter->format($start_datetime, 'custom', 'F');
            $variables['start_day'] = $date_formatter->format($start_datetime, 'custom', 'j');
          }
        }

        break;

      default:
        $node_type = ucfirst($node->getType());
        $content_type = ucfirst($node->getType());
        break;
    }

    $variables['node_type'] = $node_type;
    $variables['content_type'] = !empty($variables[$node_tag]) ? $variables[$node_tag] : $content_type;

    if (!empty($variables[$node_tag])) {
      $variables['node_tag'] = $variables[$node_tag];
    }

    // Hide share buttons for non public nodes.
    if (isset($variables['content']['shariff_field'])) {
      $field_definitions = $node->getFieldDefinitions();
      /* @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
      foreach ($field_definitions as $field_name => $field_definition) {
        // Lets fetch all the entity access fields on this current node.
        if ($field_definition->getType() === 'entity_access_field') {
          // Lets get all the values that we have for our
          // entity_access_fields.
          $field_values = $node->get($field_name)->getValue();
          foreach ($field_values as $field_value) {
            if (isset($field_value['value'])) {
              // If we have a value, and if it's not public. We better remove
              // our add to any block. We can't share any non public pages
              // anyway.
              if ($field_value['value'] !== 'public') {
                unset($variables['content']['shariff_field']);
              }
            }
          }
        }
      }
    }

    // Set original author.
    if (isset($node->field_node_original_author) && !empty($node->field_node_original_author->getValue())) {
      $original_authors = [];
      $count = 1;
      // Loop through the array of authors.
      foreach ($node->field_node_original_author->getValue() as $item) {
        $original_author = Term::load($item['target_id'])->get('name')->value;
        // Full mode shows all authors.
        // Default shows 1 author and count.
        if ($variables['view_mode'] === 'hero') {
          $original_authors[] = $original_author;
        }
        else {
          if ($count === 1) {
            $original_authors[] = $original_author;
          }
          // If there is more than 1 author and we are at the end of the array
          // then we should set the amount of authors.
          if ($count > 1 && $count === count($node->field_node_original_author->getValue())) {
            // Set plural format.
            $message = \Drupal::translation()->formatPlural(
              $count - 1,
              'and 1 other', 'and @count others'
            );

            $original_authors[] = $message;
          }
          $count++;
        }
      }

      if (!empty($original_authors)) {
        $variables['original_authors'] = $original_authors;
      }
    }

    // Set original language.
    if (isset($node->field_node_original_language) && !empty($node->field_node_original_language->getValue())) {
      $original_languages = [];
      foreach ($node->field_node_original_language->getValue() as $item) {
        // Full mode shows full language name.
        // Default shows abbreviation.
        $original_language = Term::load($item['target_id'])->get('description')->value;
        if ($variables['view_mode'] === 'full') {
          $original_language = Term::load($item['target_id'])->get('name')->value;
        }
        $original_languages[] = strip_tags($original_language);
      }

      if (!empty($original_languages)) {
        $variables['original_languages'] = $original_languages;
      }
    }

    // Set original date.
    $original_date = '';
    if (isset($node->field_node_original_date_year) && !empty($node->field_node_original_date_year->value)) {
      $original_date = $node->field_node_original_date_year->value;
    }
    if (isset($node->field_node_original_date_month) && !empty($node->field_node_original_date_month->value)) {
      $view_modes = ['teaser', 'activity', 'activity_comment', 'featured'];
      if (in_array($variables['view_mode'], $view_modes)) {
        $original_date = date('M', strtotime('00-' . $node->field_node_original_date_month->value . '-01')) . ' ' . $original_date;
      }
      else {
        $original_date = date('F', strtotime('00-' . $node->field_node_original_date_month->value . '-01')) . ' ' . $original_date;
      }
    }
    if (isset($node->field_node_original_date_day) && !empty($node->field_node_original_date_day->value)) {
      $original_date = $node->field_node_original_date_day->value . ' ' . $original_date;
    }

    if (!empty($original_date)) {
      $variables['original_date'] = $original_date;
    }
  }

}
