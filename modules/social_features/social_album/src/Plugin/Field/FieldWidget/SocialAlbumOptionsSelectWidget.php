<?php

namespace Drupal\social_album\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'social_album_options_select' widget.
 *
 * @FieldWidget(
 *   id = "social_album_options_select",
 *   label = @Translation("Select list for albums"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SocialAlbumOptionsSelectWidget extends OptionsSelectWidget {

  /**
   * The visibility options mapping.
   *
   * The associative array where keys are node options and values are the
   * corresponding post options.
   *
   * @see field.storage.node.field_content_visibility.yml
   * @see field.storage.post.field_visibility.yml
   */
  const VISIBILITY_MAPPING = [
    '0' => 'community',
    '1' => 'public',
    '2' => 'community',
    '3' => 'group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    $option = $options['_none'];

    unset($options['_none']);

    // @todo FIX issues with options, if user selects an option
    // we need to update the Post visibility accordingly with ajax.
    // imagine a user on the home stream, selecting an existing album in a
    // close group, the visibility needs to be updated to Group members, which
    // it doesn't, so for now we're not rendering any other option.
    // Return [...] + $options.
    return [
      '_none' => $option,
      '_add' => $this->t('Create new album'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $description = $element['#description'];

    unset($element['#description']);

    $state = [
      ':input[name="' . $items->getName() . '[value]"]' => [
        'value' => '_add',
      ],
    ];

    return [
      'value' => $element,
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Album name'),
        '#description' => $description,
        '#states' => [
          'visible' => $state,
          'required' => $state,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $field = $element['#parents'][0];
    $has_images = $form_state->hasValue(['field_post_image', 0, 'fids', 0]);

    if (
      $element['#value'] === '_add' &&
      ($title = $form_state->getValue([$field, 'title']))
    ) {
      if ($form_state->getTriggeringElement()['#name'] === 'op' && $has_images) {
        // Add default content visibility based on post visibility.
        if ($form_state->hasValue('field_visibility')) {
          $post_visibility = $form_state->getValue(['field_visibility', 0]);

          // Lets try and map it if possible.
          $default_visibility = self::VISIBILITY_MAPPING[$post_visibility];
        }
        else {
          $default_visibility = 'community';
        }

        $node = \Drupal::entityTypeManager()->getStorage('node')->create([
          'type' => 'album',
          'title' => $title,
          'field_content_visibility' => $default_visibility,
        ]);

        $node->save();

        $element['#value'] = $node->id();

        $form_state->set('album', TRUE);
      }
      else {
        $element['#value'] = '_none';
      }
    }
    elseif ($element['#value'] !== '_none' && !$has_images) {
      $element['#value'] = '_none';
    }

    parent::validateElement($element, $form_state);

    $form_state->setValue($field, $form_state->getValue([$field, 'value']));
  }

}
