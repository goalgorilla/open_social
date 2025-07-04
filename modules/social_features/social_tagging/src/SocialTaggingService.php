<?php

namespace Drupal\social_tagging;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\social_core\Service\MachineNameInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a custom tagging service.
 */
class SocialTaggingService implements SocialTaggingServiceInterface {

  use StringTranslationTrait;

  /**
   * The name of the hook provides supported entity types.
   */
  private const HOOK = 'social_tagging_type';

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The configuration factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * The machine name.
   */
  private MachineNameInterface $machineName;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    MachineNameInterface $machine_name,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->machineName = $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return (bool) $this->configFactory->get('social_tagging.settings')
      ->get('enable_content_tagging');
  }

  /**
   * {@inheritdoc}
   */
  public function field(
    array &$form,
    FormStateInterface $form_state,
    string $name,
    ?TranslatableMarkup $title = NULL,
    ?TranslatableMarkup $description = NULL,
    string $wrapper = self::WRAPPER,
    ?array $default_value = NULL,
    ?string $parent = NULL,
  ): bool {
    if ($parent !== NULL) {
      $element = &NestedArray::getValue($form, [$parent]);
    }
    else {
      $element = &$form;
    }

    $displayed = FALSE;

    // Get the main categories.
    $categories = $this->getCategories();

    // Placement keys.
    $keys = array_keys($this->getKeyValueOptions());
    $filter_key = NULL;
    foreach ($keys as $key) {
      // Check if form ID is one of keys to filter.
      if (str_contains((string) $form['#id'], $key) || str_contains((string) $form['#id'], str_replace('_', '-', $key))) {
        $filter_key = $key;
      }
    }
    if ($filter_key) {
      foreach ($categories as $tid => $category) {
        if ($this->termIsVisibleForEntities($tid, [$filter_key])) {
          continue;
        }
        // Unset category as it is disables for current form.
        unset($categories[$tid]);
      }
    }

    // Build form field.
    if ($default_value !== NULL) {
      // Loop over the categories.
      foreach ($categories as $tid => $category) {
        $field_name = $name . '_' . $this->machineName->transform($category);

        // Get the corresponding items.
        $options = $this->getChildren($tid);

        // Display parent item in the tags list.
        if ($this->useCategoryParent()) {
          $options = [$tid => $category] + $options;
        }

        // Only add a field if the category has any options.
        if (count($options) > 0) {
          // Add a field.
          $element[$wrapper][$field_name] = [
            '#type' => 'select2',
            '#title' => $category,
            '#multiple' => TRUE,
            '#default_value' => $default_value,
            '#group' => 'group_' . $wrapper,
            '#options' => $options,
          ];

          $displayed = TRUE;
        }
      }

      // Deny access the tags field altogether.
      $element[$name]['#access'] = FALSE;

      if ($displayed) {
        // Add a custom submit handler.
        $form['#validate'][] = '_social_tagging_entity_validate';

        $fields = (array) $form_state->get('tags');
        $form_state->set('tags', array_merge($fields, [$name]));
      }
    }
    else {
      $options = [];

      foreach ($categories as $tid => $category) {
        $options[$category] = $this->getChildren($tid);
      }

      $element[$name]['widget']['#options'] = $options;

      // Move the tags field in the group.
      $element[$wrapper][$name] = $element[$name];

      unset($element[$name]);

      $element[$wrapper][$name]['#group'] = 'group_' . $wrapper;

      $displayed = TRUE;
    }

    if ($displayed) {
      $styled = theme_get_setting('content_entity_form_style') === 'open_social';

      $element[$wrapper] += [
        '#type' => $styled ? 'details' : 'fieldset',
        '#title' => $title,
        '#description' => $description,
        '#group' => 'group_' . $wrapper,
        '#open' => TRUE,
        '#weight' => $wrapper === self::WRAPPER ? 0 : 50,
      ];
    }

    return $displayed;
  }

  /**
   * {@inheritdoc}
   */
  public function groupTypeActive(?Group $group = NULL): bool {
    if ($group) {
      return (bool) $this->configFactory->get('social_tagging.settings')
        ->get('tag_group_type_' . $group->bundle());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function groupsActive(): bool {
    $configs = $this->configFactory->get('social_tagging.settings')->getRawData();

    foreach ($configs as $cid => $data) {
      if (str_contains($cid, 'tag_group_type_')) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function profileActive(): bool {
    return (bool) $this->configFactory->get('social_tagging.settings')->get('tag_type_profile');
  }

  /**
   * {@inheritdoc}
   */
  public function hasContent(): bool {
    return count($this->getCategories()) > 0 && count($this->getAllChildren()) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function allowSplit(): bool {
    return $this->active() &&
      $this->configFactory->get('social_tagging.settings')
        ->get('allow_category_split');
  }

  /**
   * {@inheritdoc}
   */
  public function queryCondition(): string {
    return $this->configFactory->get('social_tagging.settings')
      ->get('use_and_condition') ? 'AND' : 'OR';
  }

  /**
   * {@inheritdoc}
   */
  public function useCategoryParent(): bool {
    return $this->active() &&
      $this->configFactory->get('social_tagging.settings')
        ->get('use_category_parent');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    return $this->getChildren(0);
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoriesMachineNames(): array {
    $categories_machine_names = &drupal_static(__METHOD__);
    // Return cached categories if exists, otherwise load it.
    if (isset($categories_machine_names)) {
      return $categories_machine_names;
    }

    foreach ($this->getCategories() as $tid => $label) {
      $categories_machine_names[$tid] = $this->machineName->transform($label);
    }

    return $categories_machine_names ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function termIsVisibleForEntities(int $tid, array $placement_filter_keys): bool {
    $usage = (array) $this->getTermUsageEntityTypes($tid);
    // Check if category enabled for given entities.
    return !empty(array_intersect($placement_filter_keys, $usage));
  }

  /**
   * {@inheritdoc}
   */
  public function getTermUsageEntityTypes(int|string $tid): array {
    $usage = &drupal_static(__METHOD__, []);

    // Return cached usage if exists, otherwise load it.
    if (isset($usage[$tid])) {
      return $usage[$tid];
    }

    $term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->load($tid);
    if (!$term instanceof TermInterface) {
      return $usage[$tid] = [];
    }

    // Make sure that field_category_usage still exist.
    if (
      !$term->hasField('field_category_usage') ||
      $term->get('field_category_usage')->isEmpty()
    ) {
      return $usage[$tid] = [];
    }

    $usage[$tid] = unserialize($term->get('field_category_usage')->value);

    return $usage[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren(int $category): array {
    $children = &drupal_static(__METHOD__, []);
    // Return cached children if exists, otherwise load it.
    if (isset($children[$category])) {
      return $children[$category];
    }

    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $languages = [
      // If the website is multilingual, we want to first check for the terms
      // in current language. At the moment, users do not add proper language to
      // vocabulary terms which may result in return of empty array on loadTree
      // function. So, we want to check for the terms also in default language
      // if we don't find terms in current language.
      $this->languageManager->getCurrentLanguage()->getId(),

      // Add a fallback to default language of the website if the current
      // language has no terms.
      NULL,
    ];

    foreach ($languages as $language) {
      $terms = $storage
        ->loadTree('social_tagging', $category, 1, FALSE, $language);

      if (!empty($terms)) {
        return $children[$category] = $this->prepareTermOptions($terms);
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAllChildren(): array {
    // Define as array.
    $options = [];

    // Fetch main categories.
    foreach (array_keys($this->getCategories()) as $category) {
      $options = array_merge($options, $this->getChildren($category));
    }
    // Return array.
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHierarchy(array $term_ids, string $entity_type): array {
    $tree = [];
    // Load all the terms together.
    if (!empty($terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple(array_column($term_ids, 'target_id')))) {
      // Get current language.
      // This is used to get the translated term, if available.
      $langcode = $this->languageManager->getCurrentLanguage()->getId();

      // Get splitting of fields option.
      $allowSplit = $this->allowSplit();

      // Set the route.
      if ($entity_type === 'group') {
        $route = 'view.search_groups.page_no_value';
      }
      elseif ($entity_type === 'profile') {
        $route = 'view.search_users.page_no_value';
      }
      else {
        $route = 'view.search_content.page_no_value';
      }

      // Build the hierarchy.
      foreach ($terms as $current_term) {
        // Must be a valid Term.
        if (
          !$current_term instanceof TermInterface ||
          !$current_term->isPublished()
        ) {
          continue;
        }
        // Get current terms parents.
        if ($parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadParents($current_term->id())) {
          /** @var \Drupal\taxonomy\Entity\Term $parent */
          $parent = reset($parents);
          if ($parent->hasTranslation($langcode)) {
            /** @var \Drupal\taxonomy\Entity\Term $translated_term */
            $translated_term = $parent->getTranslation($langcode);
            $category_label = $translated_term->getName();
          }
          else {
            $category_label = $parent->getName();
          }
        }
        // Or add the parent term itself if it connected to the content.
        else {
          if ($current_term->hasTranslation($langcode)) {
            /** @var \Drupal\taxonomy\Entity\Term $translated_term */
            $translated_term = $current_term->getTranslation($langcode);
            $category_label = $translated_term->getName();
          }
          else {
            $category_label = $current_term->getName();
          }
          $parent = $current_term;
        }

        // Prepare the parameter.
        $parameter = $allowSplit
          ? $this->machineName->transform($category_label) : 'tag';

        $route_parameters = [
          $parameter . '[]' => $current_term->id(),
        ];
        if ($entity_type == 'profile') {
          $route_parameters['created_op'] = '<';
        }

        // Prepare the URL for the search by term.
        $url = Url::fromRoute($route, $route_parameters)->toString();

        // Finally, prepare the hierarchy.
        $tree[$parent->id()]['title'] = $category_label;

        if ($current_term->hasTranslation($langcode)) {
          /** @var \Drupal\taxonomy\Entity\Term $translated_term */
          $translated_term = $current_term->getTranslation($langcode);
          $term_name = $translated_term->getName();
        }
        else {
          $term_name = $current_term->getName();
        }

        $tree[$parent->id()]['tags'][$current_term->id()] = [
          'url' => $url,
          'name' => $term_name,
        ];
      }
    }

    // Return the tree.
    return $tree;
  }

  /**
   * Helper function to prepare term options.
   *
   * @param array $terms
   *   Array of terms.
   */
  protected function prepareTermOptions(array $terms): array {
    $options = [];

    foreach ($terms as $category) {
      if ($category->status) {
        $options[$category->tid] = $category->name;
      }
    }

    return $options;
  }

  /**
   * Prepares settings of a supported entity type.
   *
   * @param array $items
   *   The modified settings structure is keyed by entity type identifiers.
   * @param array $item
   *   The current entity type settings.
   */
  private function type(array &$items, array $item): void {
    $entity_type = $item['entity_type'];

    unset($item['entity_type']);

    $items[$entity_type]['sets'][] = $item;
  }

  /**
   * {@inheritdoc}
   */
  public function types(bool $short = FALSE): array {
    $items = [];

    $this->moduleHandler->invokeAllWith(
      self::HOOK,
      function (callable $hook, string $module) use (&$items) {
        if (is_array($item = $hook())) {
          if (isset($item['entity_type'])) {
            $this->type($items, $item);
          }
          else {
            foreach ($item as $sub_item) {
              $this->type($items, $sub_item);
            }
          }
        }
        else {
          $items[$item] = ['sets' => [[]]];
        }
      },
    );

    $this->moduleHandler->alter(self::HOOK, $items);

    foreach ($items as &$item) {
      if (is_array($item)) {
        foreach ($item as $key => $value) {
          if (is_numeric($key)) {
            foreach ($item['sets'] as &$set) {
              $set['bundles'][] = $value;
            }

            unset($item[$key]);
          }
        }

        if (isset($item['sets'])) {
          $item += $item['sets'];

          unset($item['sets']);
        }
      }
      else {
        $item = [[]];
      }
    }

    return $short ? array_keys($items) : $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValueOptions(): array {
    $types = $this->types();
    ksort($types);
    asort($types);
    $settings = $this->configFactory->get('social_tagging.settings');
    $options = [];
    foreach ($types as $entity_type => $item) {
      $definition = $this->entityTypeManager->getDefinition($entity_type);
      if (!$definition instanceof EntityTypeInterface) {
        continue;
      }
      $item = array_filter($item);
      // Empty value means there is no bundles.
      if (empty($item)) {
        // Filter entities that are not enabled for the platform.
        if (!empty($settings->get("tag_type_$entity_type"))) {
          $options[$entity_type] = $definition->getLabel();
        }
        continue;
      }
      foreach ($item as $value) {
        $bundle_entity_type = $definition->getBundleEntityType();
        if (!isset($value['bundles']) || empty($bundle_entity_type)) {
          continue;
        }
        // Special label for nodes.
        $label = $entity_type === 'node' ? $this->t('Node') : $definition->getLabel();
        // Go foreach bundle to get key and label.
        foreach ($value['bundles'] as $bundle) {
          $bundle_entity = $this->entityTypeManager->getStorage($bundle_entity_type)->load($bundle);
          if (!$bundle_entity instanceof EntityInterface) {
            continue;
          }
          $title = $this->t('@entity_type type: @bundle', [
            '@entity_type' => $label,
            '@bundle' => $bundle_entity->label(),
          ]);
          // Filter entities that are not enabled for the platform.
          if (!empty($settings->get("tag_{$entity_type}_type_$bundle"))) {
            // Key now contains type and bundle.
            $options[$entity_type . '_' . $bundle] = $title;
          }
        }
      }
    }
    return $options;
  }

}
