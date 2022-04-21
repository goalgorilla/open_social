<?php

namespace Drupal\social_follow_taxonomy\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters users by flagged taxonomies in a view.
 *
 * @ViewsFilter("social_follow_taxonomy_follow_filter")
 */
class FollowTaxonomyViewsFilter extends TaxonomyIndexTid {

  use StringTranslationTrait;

  /**
   * The target flag entity id.
   */
  public const FLAG_ID = 'follow_term';

  /**
   * The entity manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The database connection object.
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    $options['vid'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildExtraOptionsForm($form, $form_state);

    // Change default widget to checkboxes to allow choosing multiple values.
    $form['vid']['#type'] = 'checkboxes';

    // Convert vocabulary list to array.
    $this->options['vid'] = (array) $this->options['vid'];

    // Additionally, filter by flag.
    if (!empty($form['vid']['#options'])) {
      $flag = $this->entityTypeManager
        ->getStorage('flag')
        ->load(self::FLAG_ID);

      if (!($flag instanceof FlagInterface)) {
        return;
      }

      $bundles = $flag->getApplicableBundles();
      if (empty($bundles)) {
        return;
      }

      foreach ($form['vid']['#options'] as $key => $value) {
        if (!in_array($key, $bundles)) {
          unset($form['vid']['#options'][$key]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    $vocabularies = $this->vocabularyStorage->loadMultiple((array) $this->options['vid']);
    $vocabulary_ids = array_keys($vocabularies);

    // The list of form field options.
    $options = [];

    if (empty($vocabularies) && $this->options['limit']) {
      $form['markup'] = [
        '#markup' => '<div class="js-form-item form-item">' . $this->t('An invalid vocabulary is selected. Please change it in the options.') . '</div>',
      ];
      return;
    }

    if ($this->options['type'] === 'textfield') {
      // Load terms to put into options list.
      $terms = $this->value
        ? $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($this->value)
        : [];

      $form['value'] = [
        '#title' => $this->t('Select terms'),
        '#type' => 'textfield',
        '#default_value' => EntityAutocomplete::getEntityLabels($terms),
      ];

      if ($this->options['limit']) {
        $form['value']['#type'] = 'entity_autocomplete';
        $form['value']['#target_type'] = 'taxonomy_term';
        $form['value']['#selection_settings']['target_bundles'] = $vocabulary_ids;
        $form['value']['#tags'] = TRUE;
        $form['value']['#process_default_value'] = FALSE;
      }
    }
    else {
      if (!empty($this->options['hierarchy']) && $this->options['limit']) {
        foreach ($vocabulary_ids as $vid) {
          /** @var \Drupal\taxonomy\TermInterface[] $tree */
          $tree = $this->termStorage->loadTree((string) $vid, 0, NULL, TRUE);

          if ($tree) {
            foreach ($tree as $term) {
              if (!$term->isPublished() && !$this->currentUser->hasPermission('administer taxonomy')) {
                continue;
              }
              $choice = new \stdClass();
              $choice->option = [$term->id() => str_repeat('-', $term->depth) . $this->entityRepository->getTranslationFromContext($term)->label()];
              $options[] = $choice;
            }
          }
        }
      }
      else {
        $query = $this->entityTypeManager->getStorage('taxonomy_term')
          ->getQuery()
          // @todo Sorting on vocabulary properties -
          //   https://www.drupal.org/node/1821274.
          ->sort('weight')
          ->sort('name')
          ->addTag('taxonomy_term_access');
        if (!$this->currentUser->hasPermission('administer taxonomy')) {
          $query->condition('status', 1);
        }
        if ($this->options['limit']) {
          $query->condition('vid', $vocabulary_ids, 'IN');
        }
        $terms = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->loadMultiple($query->execute());

        foreach ($terms as $term) {
          $options[$term->id()] = $this->entityRepository
            ->getTranslationFromContext($term)
            ->label();
        }
      }

      $default_value = (array) $this->value;

      if ($exposed = $form_state->get('exposed')) {
        $identifier = $this->options['expose']['identifier'];

        if (!empty($this->options['expose']['reduce'])) {
          $options = $this->reduceValueOptions($options);

          if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
            $default_value = [];
          }
        }

        if (empty($this->options['expose']['multiple'])) {
          if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
            $default_value = 'All';
          }
          elseif (empty($default_value)) {
            $keys = array_keys($options);
            $default_value = array_shift($keys);
          }
          // Due to #1464174 there is a chance that array('')
          // was saved in the admin ui.
          // Let's choose a safe default value.
          elseif ($default_value === ['']) {
            $default_value = 'All';
          }
          else {
            $copy = $default_value;
            $default_value = array_shift($copy);
          }
        }
      }

      $form['value'] = [
        '#type' => 'select',
        '#title' => $this->t('Select terms'),
        '#multiple' => TRUE,
        '#options' => $options,
        '#size' => min(9, count($options)),
        '#default_value' => $default_value,
      ];

      $user_input = $form_state->getUserInput();
      if ($exposed && isset($identifier) && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $default_value;
        $form_state->setUserInput($user_input);
      }
    }

    if (!$form_state->get('exposed')) {
      // Retain the helper option.
      $this->helper->buildOptionsForm($form, $form_state);

      // Show help text if not exposed to end users.
      $form['value']['#description'] = $this->t('Leave blank for all. Otherwise, the first selected term will be the default instead of "Any".');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    if (empty($this->value)) {
      return;
    }

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    // Add base table to avoid aliases conflicts with the main data table.
    $query->addTable('users');

    $subquery = $this->database->select('flagging', 'f');
    $subquery->addField('f', 'uid');
    $subquery->condition('f.entity_type', 'taxonomy_term');
    $subquery->condition('f.entity_id', ((array) $this->value ?: [0]), 'IN');

    $subquery->leftJoin('taxonomy_term_field_data', 't', 't.tid = f.entity_id');
    $subquery->condition('t.vid', array_filter(($this->options['vid'] ?? [0]) ?: [0]), 'IN');

    $query->addWhere($this->options['group'], 'users.uid', $subquery, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    // In views each row is cached by entity types tags provided to view with
    // base table and tables added with relationships. Even we add the tags
    // below the cache will not be invalidated because it's not
    // possible to alter cache tags for views row.
    /* @see \Drupal\views\Plugin\views\cache\CachePluginBase::getCacheTags */
    // @todo Make this workable in views.
    return [
      ...parent::getCacheTags(),
      ...['flagging_list:' . self::FLAG_ID],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $vocabularies = (array) $this->vocabularyStorage->loadMultiple($this->options['vid']);
    foreach ($vocabularies as $vocabulary) {
      $dependencies[$vocabulary->getConfigDependencyKey()][] = $vocabulary->getConfigDependencyName();
    }

    foreach ($this->termStorage->loadMultiple($this->options['value']) as $term) {
      $dependencies[$term->getConfigDependencyKey()][] = $term->getConfigDependencyName();
    }

    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->entityTypeManager
      ->getStorage('flag')
      ->load(self::FLAG_ID);

    $dependencies[$flag->getConfigDependencyKey()][] = $flag->getConfigDependencyName();

    return $dependencies;
  }

}
