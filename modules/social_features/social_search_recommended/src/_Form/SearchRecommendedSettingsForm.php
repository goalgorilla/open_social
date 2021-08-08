<?php

namespace Drupal\social_search_recommended\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure book settings for this site.
 *
 * @internal
 */
class SearchRecommendedSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'social_search_recommended.settings';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->connection = $container->get('database');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_search_recommended_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
//    Проблеми:
//    1. Тільки ноди
//    2. Треба лоадить всі сутності
//    3. Чи має бути так, що для аліаса теж відображати рекомендації?
    $config = $this->config(static::SETTINGS);

    $keywords = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'search_keywords']);

    $query = $this->connection->select('node_field_data', 'nfd');
    $query->leftJoin('node', 'n', 'n.nid = nfd.nid');
    $query->addField('n', 'uuid');
    $query->addField('nfd', 'title');
    $nodes = $query->execute()->fetchAllKeyed();

    foreach ($nodes as $uuid => $label) {
      $entities[$uuid] = $label;
    }

    $groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    foreach ($groups as $group) {
      $entities[$group->uuid()] = $group->label();
    }

    $users = $this->entityTypeManager->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      $entities[$user->uuid()] = $user->getDisplayName();
    }

    $form['partial_search'] = [
      '#type' => 'checkbox',
      '#title' => 'Partial search',
      '#description' => 'If enabled, the you will be able to write e.g. "Handbook" and it will find "employee handbook" keyword.',
      '#default_value' => $config->get('partial_search'),
    ];

    $form['create_keyword'] = [
      '#type' => 'select2',
      '#title' => 'Add new keywords',
      '#options' => [],
      '#default_value' => [],
      '#multiple' => TRUE,
      '#cardinality' => -1,
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'default:taxonomy_term',
      '#selection_settings' => [
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
        'target_bundles' => ['search_keywords' => 'search_keywords'],
        'auto_create' => TRUE,
        'auto_create_bundle' => '',
      ],
      '#autocomplete' => FALSE,
      '#autocreate' => [
        'bundle' => 'search_keywords',
        'uid' => '1',
      ],
    ];

    $form['keywords'] = [
      '#type' => 'fieldset',
      '#title' => 'Keywords',
      '#tree' => TRUE,
    ];
    foreach ($keywords as $key => $keyword) {
      $form['keywords']['keyword_' . $key] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];

      $form['keywords']['keyword_' . $key]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $keyword->label(),
        '#default_value' => $config->get('keywords.keyword_' . $key . '.enabled'),
        '#attributes' => [
          'id' => 'book_child_type' . $key,
        ],
      ];

      $form['keywords']['keyword_' . $key]['name'] = [
        '#type' => 'hidden',
        '#value' => $keyword->label(),
      ];

      $form['keywords']['keyword_' . $key]['id'] = [
        '#type' => 'hidden',
        '#value' => 'keyword_' . $key,
      ];

      $form['keywords']['keyword_' . $key]['aliases'] = [
        '#type' => 'select2',
        '#title' => t('Aliases'),
        '#default_value' => $config->get('keywords.keyword_' . $key . '.aliases'),
        '#options' => $entities ?? [],
        '#multiple' => TRUE,
        '#states' => [
          'visible' => [
            ':input[id="book_child_type' . $key . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tags = $form_state->getValue('create_keyword');

    // Create new terms.
    foreach ($tags as $tag) {
      $this->entityTypeManager->getStorage('taxonomy_term')->create([
        'vid' => 'search_keywords',
        'name' => str_replace('$ID:', '', $tag),
      ])->save();
    }

    // Set aliases.
    $config = $this->configFactory->getEditable(static::SETTINGS);
    $aliases = $form_state->getValue('keywords');

    $config
      ->set('partial_search', $form_state->getValue('partial_search'))
      ->set('keywords', $aliases)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
