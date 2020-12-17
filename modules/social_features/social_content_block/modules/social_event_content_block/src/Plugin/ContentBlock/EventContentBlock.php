<?php

namespace Drupal\social_event_content_block\Plugin\ContentBlock;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\social_content_block\ContentBlockBase;

/**
 * Provides a content block for events.
 *
 * @ContentBlock(
 *   id = "event_content_block",
 *   entityTypeId = "node",
 *   bundle = "event",
 *   fields = {
 *     "field_event_type",
 *     "field_event_content_tag",
 *     "field_event_group",
 *     "field_event_date",
 *   },
 * )
 */
class EventContentBlock extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function query(SelectInterface $query, array $fields) {
    foreach ($fields as $field_name => $field_value) {
      switch ($field_name) {
        case 'field_event_type':
          $query->innerJoin('node__field_event_type', 'et', 'et.entity_id = base_table.nid');
          $query->condition('et.field_event_type_target_id', $field_value, 'IN');
          break;

        case 'field_event_content_tag':
          $query->innerJoin('node__social_tagging', 'st', 'st.entity_id = base_table.nid');
          $query->condition('st.social_tagging_target_id', $field_value, 'IN');
          break;

        case 'field_event_group':
          $query->innerJoin('group_content_field_data', 'gc', 'gc.entity_id = base_table.nid');
          $query->condition('gc.type', '%' . $query->escapeLike('-group_node-event'), 'LIKE');
          $query->condition('gc.gid', $field_value, 'IN');
          break;

        // Filter for events in a certain date range.
        case 'field_event_date':
          $query->innerJoin('node__field_event_date', 'nfed', "nfed.entity_id = base_table.nid AND nfed.bundle = 'event'");
          $range = ['start' => NULL, 'end' => NULL];

          $start_operator = '>=';
          $end_operator = '<';
          // Apply a range based on a value.
          switch ($field_value[0]['value']) {
            case 'future':
              $range['start'] = new \DateTime();
              break;

            case 'past':
              $range['end'] = new \DateTime();
              break;

            case 'last_month':
              $range['start'] = new \DateTime('first day of last month 00:00');
              $range['end'] = new \DateTime('last day of last month 23:59');
              break;

            case 'current_month':
              $range['start'] = new \DateTime('first day of this month 00:00');
              $range['end'] = new \DateTime('last day of this month 23:59');
              break;

            case 'next_month':
              $range['start'] = new \DateTime('first day of next month 00:00');
              $range['end'] = new \DateTime('last day of next month 23:59');
              break;

            case 'ongoing':
              $range['start'] = new \DateTime('-30 days');
              $range['end'] = new \DateTime();
              $end_operator = '>';
              $query->condition('nfed.field_event_date_value', (new \DateTime())->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<');
              break;

            case 'last_30':
              $range['start'] = new \DateTime('-30 days');
              $range['end'] = new \DateTime();
              break;

            case 'next_30':
              $range['start'] = new \DateTime();
              $range['end'] = new \DateTime('+30 days');
              break;

            case 'last_14':
              $range['start'] = new \DateTime('-14 days');
              $range['end'] = new \DateTime();
              break;

            case 'next_14':
              $range['start'] = new \DateTime();
              $range['end'] = new \DateTime('+14 days');
              break;

            case 'last_7':
              $range['start'] = new \DateTime('-7 days');
              $range['end'] = new \DateTime();
              break;

            case 'next_7':
              $range['start'] = new \DateTime();
              $range['end'] = new \DateTime('+7 days');
              break;

            default:
              // If we can't handle it allow other modules a chance.
              \Drupal::moduleHandler()->alter('social_event_content_block_date_range', $range, $field_value);
          }
          // Only apply range constraints if any were actually set.
          if (isset($range['start'])) {
            $query->condition('nfed.field_event_date_value', $range['start'] instanceof \DateTime ? $range['start']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) : $range['start'], $start_operator);
          }
          if (isset($range['end'])) {
            $query->innerJoin('node__field_event_date_end', 'nfede', "nfede.entity_id = base_table.nid AND nfede.bundle = 'event'");
            $query->condition('nfede.field_event_date_end_value', $range['end'] instanceof \DateTime ? $range['end']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) : $range['end'], $end_operator);
          }
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportedSortOptions() : array {
    $defaults = parent::supportedSortOptions();
    return [
      'event_date' => 'Event date',
    ] + $defaults;
  }

}
