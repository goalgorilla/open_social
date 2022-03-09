<?php

namespace Drupal\social_profile\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image' formatter.
 */
class SocialProfileFormatter extends ImageFormatter {

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

    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();

    if ($entity_type_id !== 'user') {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      if (
        $entity_type !== NULL &&
        is_subclass_of($entity_type->getClass(), EntityOwnerInterface::class) &&
        $entity_type->hasKey('owner')
      ) {
        $form['image_link']['#options']['owner'] = $this->t('Owner');
      }
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
    $summary = parent::settingsSummary();

    if ($this->getSetting('image_link') === 'owner') {
      $summary[] = $this->t('Linked to owner');
    }

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

    if (!empty($elements) && $this->getSetting('image_link') === 'owner') {
      $entity = $items->getEntity();

      if (!$entity->isNew() && $entity instanceof EntityOwnerInterface) {
        $url = $entity->getOwner()->toUrl();

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
    }

    return $elements;
  }

}
