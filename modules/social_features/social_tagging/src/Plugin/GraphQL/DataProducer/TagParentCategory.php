<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
 *   description = @Translation("Returns the parent category (taxonomy term) of a given tag. In the tagging hierarchy, categories are parent terms and tags are child terms. Returns NULL if the tag has no parent or if the parent is unpublished. Used to navigate from a tag to its category."),
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

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected TermStorageInterface $termStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    $instance->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    return $instance;
  }

  /**
   * Resolves the parent term.
   *
   * @param \Drupal\taxonomy\TermInterface $tag
   *   The tag term.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The parent term or NULL if none exists.
   */
  public function resolve(TermInterface $tag): ?TermInterface {
    $parents = $this->termStorage->loadParents((int) $tag->id());

    if (!empty($parents)) {
      $parent = reset($parents);
      // Only return the parent if it is published.
      if ($parent->isPublished()) {
        return $parent;
      }
    }

    return NULL;
  }

}
