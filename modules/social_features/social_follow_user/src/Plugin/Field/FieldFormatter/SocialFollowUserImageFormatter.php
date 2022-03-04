<?php

namespace Drupal\social_follow_user\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image' formatter.
 */
class SocialFollowUserImageFormatter extends ImageFormatter {

  use SocialFollowUserFormatterTrait;

  public const OWNER_KEY = 'image_link';
  public const OWNER_VALUE = 'owner';

  /**
   * The entity type manager.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + ['avatar' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    if ($this->isOwnerForm()) {
      $form[self::OWNER_KEY]['#options'][self::OWNER_VALUE] = $this->t('Owner');
    }

    $form['avatar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Avatar'),
      '#default_value' => $this->getSetting('avatar'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = $this->alterOwnerSummary(
      parent::settingsSummary(),
      $this->t('Linked to owner'),
    );

    if ($this->getSetting('avatar')) {
      $summary[] = $this->t('Avatar');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if ($url = $this->getOwnerUrl($items)) {
      if ($this->getSetting('avatar')) {
        $url->mergeOptions([
          'attributes' => [
            'class' => ['avatar'],
          ],
        ]);
      }

      foreach ($elements as &$element) {
        $element['#url'] = $url;
      }
    }

    return $elements;
  }

}
