<?php

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Theme\Registry;
use Drupal\group\Entity\Group;
use Drupal\message\Entity\MessageTemplate;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\social_post\Entity\Post;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for posts.
 */
class PostViewBuilder extends EntityViewBuilder {

  /**
   * The social group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $socialGroupHelperService;

  /**
   * Constructs a new EntityViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\social_group\SocialGroupHelperService $social_group_helper_service
   *   The social group helper service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, Registry $theme_registry = NULL, SocialGroupHelperService $social_group_helper_service) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry);

    $this->socialGroupHelperService = $social_group_helper_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('social_group.helper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {

      $build[$id]['links'] = [
        '#lazy_builder' => [get_called_class() . '::renderLinks', [
          $entity->id(),
          $view_mode,
          $entity->language()->getId(),
          !empty($entity->in_preview),
        ],
        ],
      ];
    }

    if ($view_mode != 'full') {
      return;
    }

    $query = \Drupal::database()->select('message_field_data', 'm')
      ->fields('m', ['template']);

    $query->innerJoin('activity__field_activity_message', 't', 'm.mid = t.field_activity_message_target_id');

    $query->innerJoin('activity__field_activity_entity', 'e', 'e.entity_id = t.entity_id');
    $query->fields('e', ['field_activity_entity_target_id']);
    $query->condition('e.field_activity_entity_target_type', $entity->getEntityTypeId());
    $query->condition('e.field_activity_entity_target_id', $entity->id());

    $query->innerJoin('activity__field_activity_destinations', 'd', 'd.entity_id = t.entity_id');
    $query->condition('field_activity_destinations_value', ['stream_group', 'stream_profile'], 'IN');

    $template_ids = $query->execute()->fetchAllKeyed(1, 0);

    $template_types = ['create_post_group', 'create_post_profile_stream'];

    foreach ($entities as $id => $entity) {
      $template_id = $template_ids[$entity->id()];

      if (!(in_array($template_id, $template_ids) && in_array($template_id, $template_types))) {
        continue;
      }

      $account = $entity->getOwner();

      $replacements = [
        '[message:author:url:absolute]' => $account->toLink()->getUrl()->toString(),
        '[message:author:display-name]' => $account->getDisplayName(),
      ];

      if ($template_id == 'create_post_group') {
        $group_id = $this->socialGroupHelperService->getGroupFromEntity([
          'target_type' => $entity->getEntityTypeId(),
          'target_id' => $entity->id(),
        ]);

        if (empty($group_id)) {
          continue;
        }

        $group = Group::load($group_id);

        $replacements['[message:gurl]'] = $group->toLink()->getUrl()->toString();
        $replacements['[message:gtitle]'] = $group->label();
      }
      else {
        $query = \Drupal::database()->select('activity__field_activity_recipient_user', 'r')
          ->fields('r', ['field_activity_recipient_user_target_id']);

        $query->innerJoin('activity__field_activity_entity', 'e', 'e.entity_id = r.entity_id');
        $query->condition('e.field_activity_entity_target_type', $entity->getEntityTypeId());
        $query->condition('e.field_activity_entity_target_id', $entity->id());

        $query->innerJoin('activity__field_activity_destinations', 'd', 'd.entity_id = r.entity_id');
        $query->condition('field_activity_destinations_value', 'stream_profile');

        $account = User::load($query->execute()->fetchField());

        $replacements['[message:recipient-user-url]'] = $account->toLink()->getUrl()->toString();
        $replacements['[activity:field_activity_recipient_user_display_name]'] = $account->getDisplayName();
      }

      $outputs = MessageTemplate::load($template_id)->getText();
      $output = reset($outputs)->__toString();

      $build[$id]['user_id'] = ['#markup' => strtr($output, $replacements)];
    }
  }

  /**
   * Lazy_builder callback; builds a post's links.
   *
   * @param string $post_entity_id
   *   The post entity ID.
   * @param string $view_mode
   *   The view mode in which the post entity is being viewed.
   * @param string $langcode
   *   The language in which the post entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the post is currently being previewed.
   *
   * @return array
   *   A renderable array representing the post links.
   */
  public static function renderLinks($post_entity_id, $view_mode, $langcode, $is_in_preview) {
    $links = [
      '#theme' => 'links',
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      $entity = Post::load($post_entity_id)->getTranslation($langcode);
      $links['post'] = static::buildLinks($entity, $view_mode);

      // Allow other modules to alter the post links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
      ];
      \Drupal::moduleHandler()->alter('post_links', $links, $entity, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default links (Read more) for a post.
   *
   * @param \Drupal\social_post\Entity\Post $entity
   *   The post object.
   * @param string $view_mode
   *   A view mode identifier.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(Post $entity, $view_mode) {
    $links = [];

    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $links['edit'] = [
        'title' => t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl('edit-form'),
        'query' => ['destination' => \Drupal::destination()->get()],
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $links['delete'] = [
        'title' => t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form'),
        'query' => ['destination' => \Drupal::destination()->get()],
      ];
    }

    return [
      '#theme' => 'links',
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

}
