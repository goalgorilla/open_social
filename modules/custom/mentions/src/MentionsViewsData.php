<?php

/**
 * @file
 */

namespace Drupal\mentions;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the mentions entity type.
 */
class MentionsViewsData extends EntityViewsData {

  /**
   * @{inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['mentions_field_data']['table']['base']['help'] = t('Mentions entry');

    $data['mentions_field_data']['table']['wizard_id'] = 'mention';
    $data['mentions_field_data']['mid']['field']['id'] = 'mid';

    $data['mentions_field_data']['table']['group'] = t('Mentions');
    $data['mentions_field_data']['table']['entity type'] = 'mentions';

    $data['mentions_field_data']['table']['base']['title'] = t('Mentions');

    $data['mentions_field_data']['table']['base']['weight'] = 1;
    $data['mentions_field_data']['table']['base']['defaults']['field'] = 'mid';

    $data['mentions_field_data']['mid'] = [
      'title' => t('Mention ID'),
      'help' => t('Mention ID'),
      'field' => [
        'id' => 'numeric'
      ],
    ];

    $data['mentions_field_data']['entity_type'] = [
      'title' => t('Entity type'),
        'help' => t('Entity type of entity that contains mention'),
        'field' => [
          'id' => 'standard'
        ],
        'filter' => [
          'id' => 'standard'
        ],
    ];

    $data['mentions_field_data']['title'] = [
      'title' => t('Title'),
      'help' => t('Title of entity containing mention'),
      'real field' => 'mid',
      'field' => [
        'id' => 'mentions_title'
      ],
      'relationship' => [
        'base' => 'node_field_data',
        'base field' => 'title',
        'relationship field' => 'nid',
        'title' => t('Mention Title'),
        'help' => t('Mention Title'),
        'id' => 'standard',
        'label' => t('Mention Title'),
      ],
    ];

    $data['mentions_field_data']['auid'] = [
      'title' => t('Author user id'),
      'help' => t('Author user id'),
      'filter' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'standard',
      ],
      'field' => [
        'id' => 'standard',
      ],
      'relationship' => [
        'base' => 'users',
        'title' => t('User'),
        'help' => t('The user that authored the mention'),
        'id' => 'standard',
        'label' => t('Mentions user'),
      ],
    ];

    $data['mentions_field_data']['uid'] = [
      'title' => t('Mentioned user uid'),
      'help' => t('The user that is mentioned'),
      'relationship' => [
        'base' => 'users',
        'title' => t('User'),
        'help' => t('The user that is mentioned'),
        'id' => 'standard',
        'label' => t('Mentions user'),
      ],
      'filter' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'standard',
      ],
      'field' => [
        'id' => 'standard',
      ],
    ];

    $data['mentions_field_data']['created'] = [
      'title' => t('Date'),
      'help' => t('Date'),
      'field' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
    ];

    $data['mentions_field_data']['entity_id'] = [
      'title' => t('Entity id'),
      'help' => t('The unique ID of the object that contains mention'),
      'field' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
    ];

    $data['mentions_field_data']['link'] = [
      'title' => t('Link'),
      'real field' => 'mid',
      'help' => t('Link to entity that contains mention'),
      'field' => [
        'id' => 'mentions_link',
      ],
    ];

    return $data;
  }

}
