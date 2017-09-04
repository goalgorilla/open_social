<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "field" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see image-widget.html.twig
 *
 * @BootstrapPreprocess("field")
 */
class SocialBaseField extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables) {

    // For each field that doesn't need a div to wrap the content in.
    switch ($element['#field_name']) {
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

      case 'field_call_to_action_link':
        $url_options = array(
          'attributes' => array('class' => array('btn btn-primary waves-effect')),
        );
        if (isset($element[0])) {
          $element[0]['#url']->setOptions($url_options);
        }
        $url_options_1 = array(
          'attributes' => array('class' => array('btn btn-default waves-effect')),
        );
        if (isset($element[1])) {
          $element[1]['#url']->setOptions($url_options_1);
        }
        break;
    }

    if ($element['#view_mode'] == 'teaser') {
      $variables['part_of_teaser'] = TRUE;
    }

    switch ($element['#entity_type']) {
      case 'node':
      case 'post':
        $variables['bare'] = TRUE;
        break;
    }

  }

}
