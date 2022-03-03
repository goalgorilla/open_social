<?php

namespace Drupal\social_follow_user\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'string' formatter.
 */
class SocialFollowUserStringFormatter extends StringFormatter {

  use SocialFollowUserFormatterTrait;

  public const OWNER_KEY = 'link_to_owner';
  public const OWNER_VALUE = '1';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [self::OWNER_KEY => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    if ($this->isOwnerForm()) {
      $form[self::OWNER_KEY] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Link to the @entity_label', [
          '@entity_label' => $this->t('User'),
        ]),
        '#default_value' => $this->getSetting(self::OWNER_KEY),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->alterOwnerSummary(
      parent::settingsSummary(),
      $this->t('Linked to the @entity_label', [
        '@entity_label' => $this->t('User'),
      ]),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if ($url = $this->getOwnerUrl($items)) {
      foreach ($elements as &$element) {
        $element = [
          '#type' => 'link',
          '#title' => $element,
          '#url' => $url,
        ];
      }
    }

    return $elements;
  }

}
