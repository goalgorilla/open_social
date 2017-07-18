<?php

/**
 * @file
 * The field inc file for the Social base theme.
 */

use \Drupal\Core\Link;

/**
 * Implements hook_preprocess_field().
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

  if ($variables['element']['#field_name'] == 'field_call_to_action_link') {
    $url_options = array(
      'attributes' => array('class' => array('btn btn-primary waves-effect')),
    );
    if (isset($variables['element'][0])) {
      $variables['element'][0]['#url']->setOptions($url_options);
    }
    $url_options = array(
      'attributes' => array('class' => array('btn btn-default waves-effect')),
    );
    if (isset($variables['element'][1])) {
      $variables['element'][1]['#url']->setOptions($url_options);
    }
  }

}

/**
 * Implements hook_theme_suggestions_field_alter().
 */
function socialbase_theme_suggestions_field_alter(array &$suggestions, array $variables) {
  if (!empty($variables['node']) && $variables['node'] == TRUE) {
    $suggestions[] = $variables['theme_hook_original'] . '__comment';
  }
}

/**
 * Pre process the image widget.
 *
 * @param array $variables
 *    Array with variables of the image widget.
 */
function socialbase_preprocess_image_widget(array &$variables) {
  if (isset($variables['data']['remove_button'])) {
    $variables['data']['remove_button']['#button_type'] = 'flat';
  }
}
