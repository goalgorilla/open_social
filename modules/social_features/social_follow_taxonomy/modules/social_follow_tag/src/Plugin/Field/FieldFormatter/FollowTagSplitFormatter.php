<?php

declare(strict_types=1);

namespace Drupal\social_follow_tag\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\social_tag_split\Plugin\Field\FieldFormatter\TagSplitFormatter;
use Drupal\taxonomy\TermInterface;

/**
 * A formatter that uses the top taxonomy level as categories for split fields.
 *
 * @FieldFormatter(
 *   id = "social_follow_tag_split",
 *   label = @Translation("Follow tag split"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FollowTagSplitFormatter extends TagSplitFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    return [
      '#type' => 'container',
      '#attributes' => ['class' => 'social_follow_tax card__block'],
      // As per https://www.drupal.org/project/drupal/issues/2908266.
      // phpcs:ignore Squiz.Arrays.ArrayDeclaration.NoKeySpecified
      ...$elements,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function viewHierarchy(TermInterface $parent, EntityReferenceFieldItemListInterface $items, string $langcode): array {
    $field = parent::viewHierarchy($parent, $items, $langcode);

    $field['label'] = [
      '#type' => 'inline_template',
      '#template' => '<p><strong>{{label}}</strong></p>',
      '#context' => ['label' => $field['label']['#plain_text']],
    ];

    foreach ($field['items'] as $delta => $item) {
      $term = $items[$delta]->entity;
      $classes = $item['#attributes']['class'] ?? [];
      if (social_follow_taxonomy_term_followed($term)) {
        $classes[] = "term-followed";
      }
      $classes[] = 'btn-action__term';
      $field['items'][$delta] = [
        '#type' => 'container',
        '#attributes' => ['class' => 'group-action'],
        'link' => [
          '#type' => 'link',
          '#url' => $item['#url'],
          '#title' => [
            '#type' => 'inline_template',
            '#template' => '{{ title }}<svg xmlns="http://www.w3.org/2000/svg" width="12" height="11" viewBox="0 0 12 11">
              <g>
                <g>
                  <path fill="#4d4d4d" d="M6.003 9.078l3.605 2.175-.956-4.1 3.185-2.76-4.194-.356L6.003.17 4.364 4.037.17 4.393l3.185 2.76-.957 4.1z"/>
                </g>
              </g>
            </svg>',
            '#context' => ['title' => $item['#title']],
          ],
          '#attributes' => ['class' => $classes] + ($item['#attributes'] ?? []),
          '#cache' => $item['#cache'],
        ],
        'popup' => [
          '#create_placeholder' => TRUE,
          '#lazy_builder' => [
            'social_follow_tag.lazy_builder:popupLazyBuild',
            [
              $item['#url']->toString(),
              $term->id(),
              $items->getFieldDefinition()->getName(),
              $items->getFieldDefinition()->getTargetEntityTypeId(),
            ],
          ],
        ],
      ];
    }

    $field['items']['#type'] = 'container';
    $field['items']['#attributes'] = ['class' => 'group-action-wrapper'];

    return $field;
  }

}
