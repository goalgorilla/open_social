<?php

namespace Drupal\social_follow_user\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    if ($this->isOwnerForm()) {
      $form[self::OWNER_KEY]['#options'][self::OWNER_VALUE] = $this->t('Owner');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->alterOwnerSummary(
      parent::settingsSummary(),
      $this->t('Linked to owner'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if ($url = $this->getOwnerUrl($items)) {
      foreach ($elements as &$element) {
        $element['#url'] = $url;
      }
    }

    return $elements;
  }

}
