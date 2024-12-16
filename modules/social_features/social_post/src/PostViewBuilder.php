<?php

namespace Drupal\social_post;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\Element\Link;
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
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'build',
      'buildMultiple',
      'renderLinks',
    ];
  }

  /**
   * The social group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected SocialGroupHelperService $socialGroupHelperService;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

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
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\social_group\SocialGroupHelperService $social_group_helper_service
   *   The social group helper service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityRepositoryInterface $entity_repository,
    LanguageManagerInterface $language_manager,
    Registry $theme_registry,
    EntityDisplayRepositoryInterface $entity_display_repository,
    SocialGroupHelperService $social_group_helper_service,
    Connection $database,
  ) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry, $entity_display_repository);

    $this->socialGroupHelperService = $social_group_helper_service;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('entity_display.repository'),
      $container->get('social_group.helper_service'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, mixed $view_mode): void {
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {

      $build[$id]['links'] = [
        '#lazy_builder' => [static::class . '::renderLinks', [
          $entity->id(),
          $view_mode,
          $entity->language()->getId(),
          !empty($entity->in_preview),
        ],
        ],
      ];
    }

    if ($view_mode !== 'full') {
      return;
    }

    $query = $this->database->select('message_field_data', 'm')
      ->fields('m', ['template']);

    $query->innerJoin('activity__field_activity_message', 't', 'm.mid = t.field_activity_message_target_id');

    $query->innerJoin('activity__field_activity_entity', 'e', 'e.entity_id = t.entity_id');
    $query->fields('e', ['field_activity_entity_target_id']);
    $query->condition('e.field_activity_entity_target_type', $entity->getEntityTypeId());
    $query->condition('e.field_activity_entity_target_id', $entity->id());

    $query->innerJoin('activity__field_activity_destinations', 'd', 'd.entity_id = t.entity_id');
    $query->condition('field_activity_destinations_value', ['stream_group', 'stream_profile'], 'IN');

    $template_ids = [];
    $result = $query->execute();
    if ($result) {
      $template_ids = $result->fetchAllKeyed(1, 0);
    }

    $template_types = ['create_post_group', 'create_post_profile_stream'];

    foreach ($entities as $id => $entity) {
      $template_id = $template_ids[$entity->id()];

      if (!(in_array($template_id, $template_ids) && in_array($template_id, $template_types))) {
        continue;
      }
      /** @var \Drupal\social_post\Entity\PostInterface $entity
       */
      $account = $entity->getOwner();

      $replacements = [
        '[message:author:url:absolute]' => $account->toLink()->getUrl()->toString(),
        '[message:author:display-name]' => $account->getDisplayName(),
      ];

      if ($template_id === 'create_post_group') {
        $group_id = $this->socialGroupHelperService->getGroupFromEntity([
          'target_type' => $entity->getEntityTypeId(),
          'target_id' => $entity->id(),
        ]);

        if (empty($group_id)) {
          continue;
        }

        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = Group::load($group_id);

        $replacements['[message:gurl]'] = $group->toLink()->getUrl()->toString();
        $replacements['[message:gtitle]'] = $group->label();
      }
      else {
        $query = $this->database->select('activity__field_activity_recipient_user', 'r')
          ->fields('r', ['field_activity_recipient_user_target_id']);

        $query->innerJoin('activity__field_activity_entity', 'e', 'e.entity_id = r.entity_id');
        $query->condition('e.field_activity_entity_target_type', $entity->getEntityTypeId());
        $query->condition('e.field_activity_entity_target_id', $entity->id());

        $query->innerJoin('activity__field_activity_destinations', 'd', 'd.entity_id = r.entity_id');
        $query->condition('field_activity_destinations_value', 'stream_profile');

        $result = $query->execute();
        if ($result) {
          $user_id = $result->fetchField();
          /** @var \Drupal\user\Entity\User $account */
          $account = User::load($user_id);

          $replacements['[message:recipient-user-url]'] = $account->toLink()->getUrl()->toString();
          $replacements['[activity:field_activity_recipient_user_display_name]'] = $account->getDisplayName();
        }
      }

      $outputs = MessageTemplate::load($template_id)?->getText();
      if (empty($outputs)) {
        continue;
      }
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
  public static function renderLinks(string $post_entity_id, string $view_mode, string $langcode, bool $is_in_preview): array {
    $links = [
      '#theme' => 'links',
      '#pre_render' => [[Link::class, 'preRenderLinks']],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      /** @var \Drupal\social_post\Entity\Post $entity */
      $entity = Post::load($post_entity_id)?->getTranslation($langcode);
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
  protected static function buildLinks(Post $entity, string $view_mode): array {
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
