<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads the parent term of a content tag.
 *
 * @DataProducer(
 *   id = "content_tag_parent",
 *   name = @Translation("Content Tag Parent"),
 *   description = @Translation("Loads the parent category of a content tag."),
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
class ContentTagParent extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
