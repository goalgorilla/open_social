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

/**
 * Provide service for lazy rendering.
 *
 * @package Drupal\social_follow_tag
 */
class SocialFollowTagLazyBuilder implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The form builder.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Flag service.
   */
  protected FlagServiceInterface $flagService;

  /**
   * The renderer service.
   */
  protected RendererInterface $renderer;

  /**
   * The builder for flag links.
   */
  protected FlagLinkBuilderInterface $flagLinkBuilder;

  /**
   * The Current User object.
   */
  protected AccountInterface $currentUser;

  /**
   * SocialFollowTagLazyBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
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
    FlagServiceInterface $flag_service,
    RendererInterface $renderer,
    FlagLinkBuilderInterface $flag_link_builder,
    AccountInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $formBuilder;
    $this->flagService = $flag_service;
    $this->renderer = $renderer;
    $this->flagLinkBuilder = $flag_link_builder;
    $this->currentUser = $current_user;
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
  public function popupLazyBuild(string $url, $term_id, string $field, string $entity_type): array {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_id);

    if (
      $term->hasField('field_term_page_url') &&
      !$term->get('field_term_page_url')->isEmpty()
    ) {
      /** @var \Drupal\link\LinkItemInterface $link */
      $link = $term->get('field_term_page_url')->first();

      $action_label = $link->get('title')->getValue();
      $action_url = $link->getUrl()->toString();
    }

    return [
      '#theme' => 'social_tagging_popup',
      '#url' => $url,
      '#action_label' => !empty($action_label) ? $action_label : NULL,
      '#action_url' => !empty($action_url) ? $action_url : NULL,
      '#name' => $term->label(),
      '#flag' => social_follow_taxonomy_flag_link($term),
      '#followers_count' => social_follow_taxonomy_term_followers_count($term),
      '#related_entity_count' => social_follow_taxonomy_related_entity_count($term, $field, $entity_type),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'popupLazyBuild',
    ];
  }

}
