<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns the parent category of a tag.
 *
 * @DataProducer(
 *   id = "tag_parent_category",
 *   name = @Translation("Tag Parent Category"),
 *   description = @Translation("Returns the parent category (taxonomy term) of a given tag. In the tagging hierarchy, categories are parent terms and tags are child terms. Returns NULL if the tag has no parent or if the current user does not have access to view the parent. Used to navigate from a tag to its category."),
 *   produces = @ContextDefinition("entity:taxonomy_term",
 *     label = @Translation("Parent category")
 *   ),
 *   consumes = {
 *     "tag" = @ContextDefinition("entity:taxonomy_term",
 *       label = @Translation("Tag"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class TagParentCategory extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected TermStorageInterface $termStorage,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    $term_storage = $entity_type_manager->getStorage('taxonomy_term');
    $current_user = $container->get('current_user');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $term_storage,
      $current_user,
      $entity_type_manager
    );
  }

  /**
   * Resolves the parent term.
   *
   * @param \Drupal\taxonomy\TermInterface $tag
   *   The tag term.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The parent term or NULL if none exists or access is denied.
   */
  public function resolve(TermInterface $tag): ?TermInterface {
    $parents = $this->termStorage->loadParents((int) $tag->id());
    $author = $this->currentUser->getAccount();
    if ($author->isAnonymous()) {
      return NULL;
    }

    if (!empty($parents)) {
      $parent = reset($parents);
      // Only return the parent if the current user has access to view it.
      if ($parent->access('view', $author)) {
        return $parent;
      }
    }

    return NULL;
  }

}
