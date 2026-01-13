<?php

declare(strict_types=1);

namespace Drupal\social_group\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\hux\Attribute\Alter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Enhances the filters in the exposed form for group-related views.
 */
final class ViewsSorts implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructs a FormAlter instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('request_stack'),
    );
  }

  /**
   * Enhances the filters in the exposed form for the group members view.
   *
   * @param array $form
   *   The exposed form render array to be altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_views_exposed_form')]
  public function enhanceGroupMembersFilters(array &$form, FormStateInterface $form_state): void {
    if (($form['#id'] ?? NULL) !== 'views-exposed-form-group-members-page-group-members') {
      return;
    }

    // Move combine_user_name_profile_name filter to sorting_group container.
    // This makes it appear above the view alongside the sort controls.
    $this->moveSearchFilterToSortingGroup($form);
    // Enhance the "Joined" views exposed filter.
    if (isset($form['created_wrapper']['created_wrapper'])) {
      $this->enhanceJoinedFilter($form['created_wrapper']['created_wrapper']);
    }
    // Add custom reset button.
    $this->addCustomResetButton($form, $form_state);
  }

  /**
   * Moves the search filter to the sorting_group container.
   *
   * This method moves the combine_user_name_profile_name filter from the
   * exposed form block to the sorting_group container created by the
   * SortPageTop BEF plugin. The JavaScript then moves this container
   * to the page top area alongside the sort controls.
   *
   * @param array $form
   *   The form array.
   */
  protected function moveSearchFilterToSortingGroup(array &$form): void {
    // The sorting_group is created by the SortPageTop BEF plugin.
    if (!isset($form['sorting_group'])) {
      return;
    }

    // Determine which filter key exists.
    $filter_key = NULL;
    if (isset($form['combine_user_name_profile_name'])) {
      $filter_key = 'combine_user_name_profile_name';
    }
    elseif (isset($form['combine_user_name_profile_name_collapsible'])) {
      $filter_key = 'combine_user_name_profile_name_collapsible';
    }

    if (!$filter_key) {
      return;
    }

    // Add the title container before the search field. The member count will
    // be populated by JavaScript after the view loads.
    $form['sorting_group']['participants_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#value' => '',
      '#attributes' => [
        'class' => ['sorting-group__title'],
      ],
      '#weight' => -20,
    ];

    // Move the filter to the sorting_group container.
    // Set weight to display after the title but before sort controls.
    $form['sorting_group'][$filter_key] = $form[$filter_key];
    $form['sorting_group'][$filter_key]['#weight'] = -10;

    // Style the search field to match the design.
    $form['sorting_group'][$filter_key]['#type'] = 'search';
    $form['sorting_group'][$filter_key]['#attributes']['placeholder'] = $this->t('Search...');
    $form['sorting_group'][$filter_key]['#attributes']['form'] = $form['#id'];
    $form['sorting_group'][$filter_key]['#attributes']['class'][] = 'form-search';
    $form['sorting_group'][$filter_key]['#title_display'] = 'invisible';

    // Remove the original filter from the main form.
    unset($form[$filter_key]);
  }

  /**
   * Adds a custom reset button to the exposed form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function addCustomResetButton(array &$form, FormStateInterface $form_state): void {
    // Ensure actions container exists.
    if (!isset($form['actions'])) {
      $form['actions'] = [
        '#type' => 'actions',
      ];
    }

    // Add a custom reset link that navigates to the current page.
    $request = $this->requestStack->getCurrentRequest();
    $path = $request?->getPathInfo();
    if (!$path) {
      return;
    }
    $url = Url::fromUserInput($path);

    $form['actions']['custom_reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => $url,
      '#weight' => 20,
      '#attributes' => [
        'class' => ['button', 'btn', 'btn-flat', 'custom-reset-button'],
        'data-drupal-selector' => 'edit-custom-reset',
      ],
    ];
  }

  /**
   * Enhances the "Joined" filter form element for better presentation.
   *
   * @param array $element
   *   The form element to be altered, passed by reference.
   */
  private function enhanceJoinedFilter(array &$element): void {
    if (
      !isset(
        $element['#title'],
        $element['created_op'],
        $element['created']['min'],
        $element['created']['max'])
    ) {
      return;
    }

    // Convert the wrapper from "fieldset" to "container".
    $element['#type'] = 'container';
    $element['created_op']['#title'] = $element['#title'];
    $element['created_op']['#title_display'] = 'before';

    // Replace "operator" options with new titles.
    $element['created_op']['#options'] = [
      '<=' => $this->t('Before'),
      '>=' => $this->t('After'),
      'between' => $this->t('Between'),
    ];

    // Hide titles for date range filters.
    $element['created']['min']['#title_display'] =
    $element['created']['max']['#title_display'] = 'invisible';
  }

}
