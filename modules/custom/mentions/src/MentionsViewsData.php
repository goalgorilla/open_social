<?php

namespace Drupal\mentions;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the mentions entity type.
 */
class MentionsViewsData extends EntityViewsData {

  /**
   * Get the views data.
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['mentions_field_data']['table']['base']['help'] = $this->t('Mentions entry');

    $data['mentions_field_data']['table']['wizard_id'] = 'mention';
    $data['mentions_field_data']['mid']['field']['id'] = 'mid';

    $data['mentions_field_data']['table']['group'] = $this->t('Mentions');
    $data['mentions_field_data']['table']['entity type'] = 'mentions';

    $data['mentions_field_data']['table']['base']['title'] = $this->t('Mentions');

    $data['mentions_field_data']['table']['base']['weight'] = 1;
    $data['mentions_field_data']['table']['base']['defaults']['field'] = 'mid';

    $data['mentions_field_data']['mid'] = [
      'title' => $this->t('Mention ID'),
      'help' => $this->t('Mention ID'),
      'field' => [
        'id' => 'numeric',
      ],
    ];

    $data['mentions_field_data']['entity_type'] = [
      'title' => $this->t('Entity type'),
      'help' => $this->t('Entity type of entity that contains mention'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'standard',
      ],
    ];

    $data['mentions_field_data']['title'] = [
      'title' => $this->t('Title'),
      'help' => $this->t('Title of entity containing mention'),
      'real field' => 'mid',
      'field' => [
        'id' => 'mentions_title',
      ],
      'relationship' => [
        'base' => 'node_field_data',
        'base field' => 'title',
        'relationship field' => 'nid',
        'title' => $this->t('Mention Title'),
        'help' => $this->t('Mention Title'),
        'id' => 'standard',
        'label' => $this->t('Mention Title'),
      ],
    ];

    $data['mentions_field_data']['auid'] = [
      'title' => $this->t('Author user id'),
      'help' => $this->t('Author user id'),
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
        'title' => $this->t('User'),
        'help' => $this->t('The user that authored the mention'),
        'id' => 'standard',
        'label' => $this->t('Mentions user'),
      ],
    ];

    $data['mentions_field_data']['uid'] = [
      'title' => $this->t('Mentioned user uid'),
      'help' => $this->t('The user that is mentioned'),
      'relationship' => [
        'base' => 'users',
        'title' => $this->t('User'),
        'help' => $this->t('The user that is mentioned'),
        'id' => 'standard',
        'label' => $this->t('Mentions user'),
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
      'title' => $this->t('Date'),
      'help' => $this->t('Date'),
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
      'title' => $this->t('Entity id'),
      'help' => $this->t('The unique ID of the object that contains mention'),
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
      'title' => $this->t('Link'),
      'real field' => 'mid',
      'help' => $this->t('Link to entity that contains mention'),
      'field' => [
        'id' => 'mentions_link',
      ],
    ];

    return $data;
  }

}
