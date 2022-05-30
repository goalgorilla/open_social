<?php

namespace Drupal\social_follow_tag;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagLinkBuilderInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\social_tagging\SocialTaggingService;

/**
 * Provide service for lazy rendering.
 *
 * @package Drupal\social_follow_tag
 */
class SocialFollowTagLazyBuilder implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The route match.
   *
   * @var \Drupal\social_tagging\SocialTaggingService
   */
  protected $tagService;

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The builder for flag links.
   *
   * @var \Drupal\flag\FlagLinkBuilderInterface
   */
  protected $flagLinkBuilder;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * SocialFollowTagLazyBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\social_tagging\SocialTaggingService $tagging_service
   *   The tag service.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   Flag service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\flag\FlagLinkBuilderInterface $flag_link_builder
   *   The builder for flag links.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FormBuilderInterface $formBuilder,
    SocialTaggingService $tagging_service,
    FlagServiceInterface $flag_service,
    RendererInterface $renderer,
    FlagLinkBuilderInterface $flag_link_builder,
    AccountInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $formBuilder;
    $this->tagService = $tagging_service;
    $this->flagService = $flag_service;
    $this->renderer = $renderer;
    $this->flagLinkBuilder = $flag_link_builder;
    $this->currentUser = $current_user;
  }

  /**
   * Returns tags for lazy builder.
   */
  public function lazyBuild() {
    $identifiers = [];

    // Get the tag category identifier that is used as a parameter in the URL.
    // It takes on the value of the parent term if the allow_category_split
    // settings is enabled or equal to the default name of the filter (tag).
    if ($this->tagService->allowSplit()) {
      foreach ($this->tagService->getCategories() as $tid => $value) {
        if (!empty($this->tagService->getChildren($tid))) {
          $identifiers[] = social_tagging_to_machine_name($value);
        }
      }
    }
    else {
      $identifiers = ['tag'];
    }
    // Get term id from url parameters.
    $term_ids = [];
    foreach ($identifiers as $identifier) {
      if (isset($_GET[$identifier])) {
        $term_ids = array_merge($term_ids, $_GET[$identifier]);
      }
    }

    $tags = [];
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadMultiple($term_ids);
    foreach ($terms as $term) {

      // Show only tags followed by user.
      if (social_follow_taxonomy_term_followed($term)) {
        $tags[$term->id()] = [
          'name' => $term->getName(),
          'flag' => social_follow_taxonomy_flag_link($term),
          'related_entity_count' => social_follow_taxonomy_related_entity_count($term, 'social_tagging'),
          'followers_count' => social_follow_taxonomy_term_followers_count($term),
        ];
      }
    }

    if (!empty($tags)) {
      $build = [
        '#theme' => 'search_follow_tag',
        '#tagstitle' => $this->t('Tags'),
        '#tags' => $tags,
      ];

      // Generate cache tags.
      foreach ($tags as $tag_id => $tag) {
        $build['#cache']['tags'][] = "follow_tag_node:$tag_id";
      }

      return $build;
    }

    return [];
  }

  /**
   * Returns render array for tag follow popup.
   *
   * @param string $url
   *   ULR of related content.
   * @param string|int $term_id
   *   Taxonomy term ID.
   * @param string $field
   *   Entity field name related to taxonomy.
   * @param string $entity_type
   *   Entity type for related content counter.
   *
   * @return array
   *   Render array.
   */
  public function popupLazyBuild($url, $term_id, $field, $entity_type) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_id);
    return [
      '#theme' => 'social_tagging_popup',
      '#url' => $url,
      '#name' => $term->label(),
      '#flag' => social_follow_taxonomy_flag_link($term),
      '#followers_count' => social_follow_taxonomy_term_followers_count($term),
      '#related_entity_count' => social_follow_taxonomy_related_entity_count($term, $field, $entity_type),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'lazyBuild',
      'popupLazyBuild',
    ];
  }

}
