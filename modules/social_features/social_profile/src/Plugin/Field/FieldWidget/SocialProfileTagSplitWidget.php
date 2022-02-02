<?php

namespace Drupal\social_profile\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\select2\Plugin\Field\FieldWidget\Select2EntityReferenceWidget;
use Drupal\social_profile\SocialProfileTagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialProfileTagSplitWidget.
 *
 * @FieldWidget (
 *   id = "social_profile_tag_split",
 *   label = @Translation("Social Profile Tag Split"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class SocialProfileTagSplitWidget extends Select2EntityReferenceWidget {

  /**
   * The social profile tag service.
   *
   * @var \Drupal\social_profile\SocialProfileTagServiceInterface
   */
  protected $profileTagService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $widget = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $widget->setSocialProfileTagService($container->get('social_profile.tag_service'));
    return $widget;
  }

  /**
   * Set the social profile tag service.
   *
   * @param \Drupal\social_profile\SocialProfileTagServiceInterface $social_profile_tag_service
   *   The social profile tag service.
   */
  protected function setSocialProfileTagService(SocialProfileTagServiceInterface $social_profile_tag_service) {
    $this->profileTagService = $social_profile_tag_service;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Do not render field if no options or not active.
    if (
      !$this->profileTagService->isActive() ||
      !$this->profileTagService->hasContent() ||
      empty($element['#options'])
    ) {
      return [];
    }

    // Only for fields related to taxonomy terms.
    if ($element['#target_type'] !== 'taxonomy_term') {
      return $element;
    }

    // Render all value if split tag disabled.
    if (!$this->profileTagService->allowSplit()) {
      $options = &$element['#options'];
      $term_ids = array_keys($options);
      $options = $this->profileTagService->getTermOptionNames($term_ids);
      return $element;
    }

    // Get default values.
    $default_value = $element['#default_value'];

    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      '#element_validate' => [[get_class($this), 'validateElement']],
      '#access' => FALSE,
    ];

    // Get the main categories.
    $categories = $this->profileTagService->getCategories();
    foreach ($categories as $tid => $category) {
      $field_name = 'profile_tagging_' . $this->profileTagService->tagLabelToMachineName($category);

      // Get the corresponding items.
      $options = $this->profileTagService->getChildrens($tid);

      // Display parent item in the tags list.
      if ($this->profileTagService->useCategoryParent()) {
        $options = [$tid => $category] + $options;
      }

      // Only add a field if the category has any options.
      if (count($options) > 0) {
        $element[$field_name] = [
          '#type' => 'select2',
          '#title' => $category,
          '#multiple' => TRUE,
          '#default_value' => $default_value,
          '#options' => $options,
        ];

        $element['#access'] = TRUE;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\social_profile\SocialProfileTagServiceInterface $profile_tag_service */
    $profile_tag_service = \Drupal::service('social_profile.tag_service');
    if ($profile_tag_service->allowSplit()) {
      $build_info = $form_state->getBuildInfo();
      if ($build_info['form_id'] === 'user_register_form') {
        $field_element = ['entity_profile', $element['#field_name']];
      }
      else {
        $field_element = $element['#field_name'];
      }
      $value = $form_state->getValue($field_element);
      $field_value = [];
      // Get the main categories.
      $categories = $profile_tag_service->getCategories();
      foreach ($categories as $tid => $category) {
        $field_name = 'profile_tagging_' . $profile_tag_service->tagLabelToMachineName($category);
        if (isset($value[$field_name])) {
          $field_value += $value[$field_name];
        }
      }
      $form_state->setValue($field_element, $field_value);
    }
    else {
      parent::validateElement($element, $form_state);
    }
  }

}
