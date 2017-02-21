<?php

use \Drupal\Core\Link;

/**
 * Implements hook_preprocess_field().
 * @param $variables
 */
function socialbase_preprocess_field(&$variables) {
  // For each field that doesn't need a div to wrap the content in.
  switch ($variables['element']['#field_name']) {
    case 'field_profile_image':
    case 'field_profile_function':
    case 'field_profile_organization':
    case 'field_group_description':
    case 'field_group_address':
    case 'field_group_location':
    case 'field_group_image':
    case 'field_topic_image':
    case 'field_comment_body':
    case 'field_activity_output_text':
    case 'field_activity_entity':
      $variables['bare'] = TRUE;
      break;
  }

  if ($variables['element']['#view_mode'] == 'teaser') {
    $variables['part_of_teaser'] = TRUE;
  }

  switch ($variables['element']['#entity_type']) {
    case 'node':
    case 'post':
      $variables['bare'] = TRUE;
      break;
  }

  switch ($variables['element']['#formatter']) {
    case 'comment_default':
    case 'comment_group_content':
      $variables['node'] = TRUE;
      break;

    case 'comment_node':
      $variables['stream'] = TRUE;
      break;
  }

  if ($variables['element']['#formatter'] == 'comment_node') {
    $node = $variables['element']['#object'];
    $link_options = array(
      'fragment' => 'comment-form',
      'attributes' => array(
        'class' => array(
          'btn btn-flat brand-text-primary',
        ),
      ),
    );

    $urlwithoptions = $node->toUrl('canonical', $link_options);
    $variables['nodelink'] = Link::fromTextAndUrl(t('Comment'), $urlwithoptions);
  }

}

/**
 * Implements hook_theme_suggestions_field_alter().
 * @param array $suggestions
 * @param array $variables
 */
function socialbase_theme_suggestions_field_alter(array &$suggestions, array $variables) {
  if (!empty($variables['node']) && $variables['node'] == TRUE) {
    $suggestions[] = $variables['theme_hook_original'] . '__comment';
  }
}

/**
 * @param $variables
 */
function socialbase_preprocess_image_widget(&$variables) {
  if (isset($variables['data']['remove_button'])) {
    $variables['data']['remove_button']['#button_type'] = 'flat';
  }
}
