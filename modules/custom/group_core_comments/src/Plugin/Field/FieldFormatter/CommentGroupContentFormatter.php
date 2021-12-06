<?php

namespace Drupal\group_core_comments\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'comment_group_content' formatter.
 *
 * @FieldFormatter(
 *   id = "comment_group_content",
 *   label = @Translation("Comment on group content"),
 *   field_types = {
 *     "comment"
 *   }
 * )
 */
class CommentGroupContentFormatter extends CommentDefaultFormatter {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * TRUE if the request is a XMLHttpRequest.
   *
   * @var bool
   */
  private $isXmlHttpRequest;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
      $container->get('entity_display.repository'),
      $container->get('renderer'),
      $container->get('request_stack')->getCurrentRequest()->isXmlHttpRequest()
    );
  }

  /**
   * Constructs a new CommentDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer interface.
   * @param bool $is_xml_http_request
   *   TRUE if the request is a XMLHttpRequest.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFormBuilderInterface $entity_form_builder,
    RouteMatchInterface $route_match,
    EntityDisplayRepositoryInterface $entity_display_repository,
    RendererInterface $renderer,
    $is_xml_http_request
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $current_user,
      $entity_type_manager,
      $entity_form_builder,
      $route_match,
      $entity_display_repository
    );

    $this->renderer = $renderer;
    $this->isXmlHttpRequest = $is_xml_http_request;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $output = parent::viewElements($items, $langcode);
    $entity = $items->getEntity();

    // Exclude entities without the set id.
    if (!empty($entity->id())) {
      $group_contents = GroupContent::loadByEntity($entity);
    }

    if (!empty($group_contents)) {
      // Add cache contexts.
      $output['#cache']['contexts'][] = 'route.group';
      $output['#cache']['contexts'][] = 'user.group_permissions';

      $account = $this->currentUser;
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = reset($group_contents)->getGroup();
      $group_url = $group->toUrl('canonical', ['language' => $group->language()]);

      $access_post_comments = $this->getPermissionInGroups('post comments', $account, $group_contents, $output);
      if ($access_post_comments->isForbidden()) {
        $join_directly_bool = FALSE;

        if ($group->getGroupType()->id() === 'flexible_group') {
          if (social_group_flexible_group_can_join_directly($group)) {
            $join_directly_bool = TRUE;
          }
        }
        elseif ($group->hasPermission('join group', $account)) {
          $join_directly_bool = TRUE;
        }

        // If a user can't join directly, about page makes more sense.
        if (!$join_directly_bool) {
          $group_url = Url::fromRoute('view.group_information.page_group_about', ['group' => $group->id()]);
        }

        if ($join_directly_bool) {
          $action = [
            'type' => 'join_directly',
            'label' => $this->t('Join group'),
            'url' => Url::fromRoute('group_core_comments.quick_join_group', ['group' => $group->id()]),
            'class' => 'btn btn-accent',
          ];
        }
        elseif ($group->hasPermission('request group membership', $account)) {
          $url = Url::fromRoute('entity.group.canonical', ['group' => $group->id()]);
          $url = $url->setOption('query', [
            'requested-membership' => $group->id(),
          ]);
          $action = [
            'type' => 'request_only',
            'label' => $this->t('Request only'),
            'url' => $url,
            'class' => 'btn btn-accent',
          ];
        }
        else {
          $action = [
            'type' => 'invitation_only',
            'label' => $this->t('Invitation only'),
            'url' => NULL,
            'class' => 'btn btn-accent disabled',
          ];
        }

        $description = $this->t('You are not allowed to comment on content in a group you are not member of.');

        $group_image = NULL;
        if ($group->hasField('field_group_image') && !$group->get('field_group_image')->isEmpty()) {
          /** @var \Drupal\file\FileInterface $image_file */
          $image_file = $group->get('field_group_image')->entity;
          $group_image = [
            '#theme' => 'image_style',
            '#style_name' => 'social_xx_large',
            '#uri' => $image_file->getFileUri(),
          ];
        }

        $output[0]['comment_form'] = [
          '#theme' => 'comments_join_group',
          '#description' => $description,
          '#group_info' => [
            'image' => $group_image,
            'label' => $group->label(),
            'type' => $group->getGroupType()->label(),
            'members_count' => count($group->getMembers()),
            'url' => $group_url->toString(),
          ],
          '#action' => $action,
        ];
      }

      $access_view_comments = $this->getPermissionInGroups('access comments', $account, $group_contents, $output);
      if ($access_view_comments->isForbidden()) {
        $description = $this->t('You are not allowed to view comments on content in a group you are not member of. You can join the group @group_link.',
          [
            '@group_link' => Link::fromTextAndUrl($this->t('here'), $group_url)
              ->toString(),
          ]
        );
        unset($output[0]);
        $output[0]['comments'] = [
          '#markup' => $description,
        ];
      }

    }

    if (!empty($output[0]['comments']) && !$this->isXmlHttpRequest) {
      $comment_settings = $this->getFieldSettings();
      $output[0]['comments'] = [
        '#lazy_builder' => [
          'social_comment.lazy_renderer:renderComments',
          [
            $entity->getEntityTypeId(),
            $entity->id(),
            $comment_settings['default_mode'],
            $items->getName(),
            $comment_settings['per_page'],
            $this->getSetting('pager_id'),
            $this->getSetting('view_mode'),
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }
    return $output;
  }

  /**
   * Checks if account was granted permission in group.
   */
  protected function getPermissionInGroups($perm, AccountInterface $account, $group_contents, &$output) {
    foreach ($group_contents as $group_content) {
      $group = $group_content->getGroup();

      // Add cacheable dependency.
      $membership = $group->getMember($account);
      $this->renderer->addCacheableDependency($output, $membership);

      if ($group->hasPermission($perm, $account)) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    // Fallback.
    return AccessResult::forbidden()->cachePerUser();
  }

}
