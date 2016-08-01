<?php

/**
 * @file
 * Builds placeholder replacement tokens for group-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function group_token_info() {
  $type = [
    'name' => t('Group'),
    'description' => t('Tokens related to individual groups.'),
    'needs-data' => 'group',
  ];

  $group['id'] = [
    'name' => t('Group ID'),
    'description' => t('The unique ID of the group.'),
  ];

  $group['type'] = [
    'name' => t('Group type'),
    'description' => t('The machine name of the group type.'),
  ];

  $group['type-name'] = [
    'name' => t('Group type name'),
    'description' => t('The human-readable name of the group type.'),
  ];

  $group['title'] = [
    'name' => t('Title'),
  ];

  $group['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the group.'),
  ];

  $group['edit-url'] = [
    'name' => t('Edit URL'),
    'description' => t('The URL of the group\'s edit page.'),
  ];

  $group['created'] = [
    'name' => t('Date created'),
    'type' => 'date',
  ];

  $group['changed'] = [
    'name' => t('Date changed'),
    'description' => t('The date the group was most recently updated.'),
    'type' => 'date',
  ];

  $group['author'] = [
    'name' => t('Author'),
    'type' => 'user',
  ];

  return [
    'types' => ['group' => $type],
    'tokens' => ['group' => $group],
  ];
}

/**
 * Implements hook_tokens().
 */
function group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = [];

  if ($type == 'group' && !empty($data['group'])) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $data['group'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $group->id();
          break;

        case 'type':
          $replacements[$original] = $group->bundle();
          break;

        case 'type-name':
          $replacements[$original] = $group->getGroupType()->label();
          break;

        case 'title':
          $replacements[$original] = $group->label();
          break;

        case 'langcode':
          $replacements[$original] = $group->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $group->toUrl('canonical', $url_options)->toString();
          break;

        case 'edit-url':
          $replacements[$original] = $group->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $group->getOwner();
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($group->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($group->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $group->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $group->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $group->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
