<?php

namespace Drupal\social_profile\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiTerm;
use Drupal\social_profile\SocialProfileTagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a filter for filtering on taxonomy term references.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("social_search_api_split_profile_terms")
 */
class SocialSearchApiSplitProfileTerms extends SearchApiTerm {

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
    $filter = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $filter->setSocialProfileTagService($container->get('social_profile.tag_service'));
    return $filter;
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
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // Use only for profile tag field.
    if ($this->field !== 'field_profile_profile_tag') {
      return;
    }

    $element = &$form['value'];

    // Do not render field if no options or not active.
    if (
      !$this->profileTagService->isActive() ||
      !$this->profileTagService->hasContent() ||
      empty($element['#options'])
    ) {
      $element = [];
    }

    // Render all value if split tag disabled.
    if (!$this->profileTagService->allowSplit()) {
      $term_ids = [];
      $element['#type'] = 'select2';
      $options = &$element['#options'];
      foreach ($options as $option) {
        $term_ids[] = array_keys($option->option)[0];
      }
      $options = $this->profileTagService->getTermOptionNames($term_ids);
      return;
    }

    // Get selected values.
    $identifier = $this->options['expose']['identifier'];
    $default_value = $form_state->getUserInput()[$identifier];

    // Wrapper.
    $element = [
      '#type' => 'details',
      '#open' => TRUE,
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
          '#value' => $default_value,
          '#options' => $options,
          '#name' => 'profile_tag',
        ];

        $element['#access'] = TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (
      $this->field !== 'field_profile_profile_tag' ||
      !$this->profileTagService->allowSplit()
    ) {
      parent::validateExposed($form, $form_state);
      return;
    }

    $profile_tag_values = [];
    $identifier = $this->options['expose']['identifier'];

    // Get the main categories.
    $categories = $this->profileTagService->getCategories();

    // Get form values.
    $form_values = $form_state->getValues();
    foreach ($categories as $tid => $category) {
      $field_name = 'profile_tagging_' . $this->profileTagService->tagLabelToMachineName($category);
      if (isset($form_values[$field_name])) {
        $profile_tag_values += $form_values[$field_name];
        unset($form_values[$field_name]);
      }
    }
    $form_values[$identifier] = $profile_tag_values;
    $form_state->setValues($form_values);
    parent::validateExposed($form, $form_state);
  }

}
