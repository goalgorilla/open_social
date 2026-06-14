<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\Entity\Node;
use Drupal\social_comment\HiddenCommentFieldAccessInterface;

/**
 * Pre-processes variables for the "field" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see image-widget.html.twig
 *
 * @BootstrapPreprocess("field")
 */
class Field extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables): void {
    // For each field that doesn't need a div to wrap the content in.
    switch ($element['#field_name']) {
      case 'field_profile_image':
      case 'field_group_description':
      case 'field_group_address':
      case 'field_group_location':
      case 'field_group_image':
      case 'field_topic_image':
      case 'field_comment_body':
      case 'field_activity_output_text':
      case 'field_activity_entity':
      case 'field_profile_first_name':
      case 'field_profile_last_name':
        $variables['bare'] = TRUE;
        break;

      case 'field_call_to_action_link':
        $url_options = [
          'attributes' => ['class' => ['btn btn-primary']],
        ];
        if (isset($element[0])) {
          $url_options += $element[0]['#url']->getOptions();
          $element[0]['#url']->setOptions($url_options);
        }
        $url_options_1 = [
          'attributes' => ['class' => ['btn btn-default']],
        ];
        if (isset($element[1])) {
          $url_options_1 += $element[1]['#url']->getOptions();
          $element[1]['#url']->setOptions($url_options_1);
        }
        break;
    }

    if ($element['#view_mode'] == 'teaser') {
      $variables['part_of_teaser'] = TRUE;

      // Add line clamp to our summary / intro fields. These get rendered
      // in templates/profile/profile--profile--teaser.html.twig
      // but aren't taking into account the height.
      // See https://github.com/goalgorilla/open_social/pull/2846 and #3272691.
      if ($element['#field_name'] === 'field_profile_self_introduction' ||
          $element['#field_name'] === 'field_profile_summary') {
        $variables['items'][0]['attributes']->addClass('line-clamp');
      }
    }

    // Adds the comment title with the amount of comments, done in here
    // so Ajax can also update this title. Node preprocess doesn't get called
    // when Ajax updates the below fields.
    if ($element['#field_type'] === 'comment') {
      // Grab the attached Event or Topic.
      $attached = $element->getArray();
      $node = !empty($attached['#object']) ? $attached['#object'] : NULL;
      // Count the number of comments placed on a Node.
      if ($node instanceof Node) {
        $comment_field_name = $element['#field_name'];
        // Discussion comments are counted using their formatter.
        if ($node->bundle() !== 'discussion') {
          $comment_count = (int) $node->get($comment_field_name)->comment_count;
          // Add it to the title.
          $variables['comment_count'] = $comment_count;
        }
        $variables['comment_open'] = (int) $node->get($comment_field_name)->status === CommentItemInterface::OPEN;

        if ((int) $node->get($comment_field_name)->status === CommentItemInterface::HIDDEN) {
          if (\Drupal::moduleHandler()->moduleExists('social_comment')) {
            $hidden_access = \Drupal::service('social_comment.hidden_comment_field_access')
              ->accessHiddenField(\Drupal::currentUser(), $node, $comment_field_name);
          }
          else {
            $hidden_access = \Drupal::service(HiddenCommentFieldAccessInterface::class)
              ->accessHiddenField(\Drupal::currentUser(), $node, $comment_field_name);
          }

          $cache_target = ['#cache' => $variables['#cache'] ?? []];
          $cache_target['#cache']['contexts'][] = 'user';
          $cache_target['#cache']['contexts'][] = 'user.permissions';
          CacheableMetadata::createFromRenderArray($cache_target)
            ->addCacheableDependency($hidden_access)
            ->applyTo($cache_target);
          $variables['#cache'] = $cache_target['#cache'];

          if (!$hidden_access->isForbidden()) {
            $variables['comment_hidden_viewable'] = TRUE;
          }
        }
      }
    }

    switch ($element['#entity_type']) {
      case 'node':
      case 'post':
        $variables['bare'] = TRUE;
        break;
    }

  }

}
