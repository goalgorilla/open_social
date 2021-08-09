<?php

namespace Drupal\social_search_recommended\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SearchRecommendedBlock' block.
 *
 * @Block(
 *  id = "search_recommended_block",
 *  admin_label = @Translation("Search recommended block"),
 * )
 */
class SearchRecommendedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routeMatch = $container->get('current_route_match');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $search_keys = $this->routeMatch->getParameter('keys');

    /** @var \Drupal\taxonomy\Entity\Term[] $search_keywords */
    $search_keywords = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'search_keywords']);

    $nodes = [];
    $groups = [];
    $profiles = [];

    $config = $this->getConfiguration();
    $is_partial_search = $config['partial_search'];
    $total_items = (int) $config['total_items'];

    foreach ($search_keywords as $term) {
      if ($is_partial_search && strpos(strtolower($term->label()), strtolower($search_keys)) !== FALSE) {
        $nodes = array_merge($nodes, $this->getReferencedEntities('node', $term));
        $groups = array_merge($groups, $this->getReferencedEntities('group', $term));
        $profiles = array_merge($profiles, $this->getReferencedEntities('profile', $term));

        continue;
      }

      if (!$is_partial_search && strtolower($term->label()) === strtolower($search_keys)) {
        $nodes = $this->getReferencedEntities('node', $term);
        $groups = $this->getReferencedEntities('group', $term);
        $profiles = $this->getReferencedEntities('profile', $term);

        break;
      }
    }

    $all_entities = array_merge($nodes, $groups, $profiles);

    $items = 0;
    foreach ($all_entities as $entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $build[] = $view_builder->view($entity, 'small_teaser');

      $items++;
      if ($total_items === $items) {
        break;
      }
    }

    return $build ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['partial_search'] = [
      '#type' => 'checkbox',
      '#title' => 'Partial search',
      '#description' => 'If enabled, the you will be able to write e.g. "Handbook" and it will find "employee handbook" keyword.',
      '#default_value' => !empty($config['partial_search']),
    ];

    $form['total_items'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Number of items'),
      '#default_value' => $config['total_items'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'partial_search' => FALSE,
      'total_items' => 3,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['partial_search'] = $form_state->getValue('partial_search');
    $this->configuration['total_items'] = $form_state->getValue('total_items');
  }

  /**
   * Get referenced entities for the specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   A taxonomy term object.
   *
   * @return array
   *   Array of referenced entities.
   */
  private function getReferencedEntities(string $entity_type_id, Term $term): array {
    $field_name = 'field_' . $entity_type_id . '_entities';

    if ($term->hasField('field_' . $entity_type_id . '_entities') && !$term->{$field_name}->isEmpty()) {
      $entities = $term->{$field_name}->referencedEntities();
    }

    return $entities ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
