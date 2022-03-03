<?php

namespace Drupal\social_follow_user\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Plugin implementation of the 'string' formatter.
 */
class SocialFollowUserFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + ['link_to_owner' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $entity_type = $this->entityTypeManager->getDefinition(
      $this->fieldDefinition->getTargetEntityTypeId(),
    );

    if (
      $entity_type !== NULL &&
      $entity_type->id() !== 'user' &&
      is_subclass_of($entity_type->getClass(), EntityOwnerInterface::class) &&
      $entity_type->hasKey('owner')
    ) {
      $form['link_to_owner'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Link to the @entity_label', [
          '@entity_label' => $this->t('User'),
        ]),
        '#default_value' => $this->getSetting('link_to_owner'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('link_to_owner')) {
      $summary[] = $this->t('Linked to the @entity_label', [
        '@entity_label' => $this->t('User'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if (!empty($elements) && $this->getSetting('link_to_owner')) {
      $entity = $items->getEntity();

      if (!$entity->isNew() && $entity instanceof EntityOwnerInterface) {
        $url = $this->getEntityUrl($entity->getOwner());

        foreach ($elements as &$element) {
          $element = [
            '#type' => 'link',
            '#title' => $element,
            '#url' => $url,
          ];
        }
      }
    }

    return $elements;
  }

}
