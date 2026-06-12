<?php

namespace Drupal\group_core_comments\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
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
use Drupal\group\Entity\GroupRelationship;
use Drupal\group_core_comments\Access\HiddenCommentFieldAccessLocator;
use Drupal\social_comment\HiddenCommentFieldAccessInterface;
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
   * Hidden comment field access service.
   */
  protected HiddenCommentFieldAccessInterface $hiddenCommentFieldAccess;

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
      $container->get('request_stack')->getCurrentRequest()->isXmlHttpRequest(),
      HiddenCommentFieldAccessLocator::get($container),
    );
  }

  /**
   * Constructs a new CommentDefaultFormatter.
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
    bool $is_xml_http_request,
    HiddenCommentFieldAccessInterface $hidden_comment_field_access,
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
    $this->hiddenCommentFieldAccess = $hidden_comment_field_access;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $gate_elements = [];
    $entity = $items->getEntity();
    $values = $items->getValue();
    $status = !empty($values) ? (int) $values[0]['status'] : 0;
    $field_name = $this->fieldDefinition->getName();

    $hidden_access = NULL;
    if ($status === CommentItemInterface::HIDDEN) {
      $gate_elements['#cache']['contexts'][] = 'user';
      $gate_elements['#cache']['contexts'][] = 'user.permissions';
      $hidden_access = $this->hiddenCommentFieldAccess->accessHiddenField(
        $this->currentUser,
        $entity,
        $field_name,
      );
      CacheableMetadata::createFromRenderArray($gate_elements)
        ->addCacheableDependency($hidden_access)
        ->applyTo($gate_elements);
      if ($hidden_access->isForbidden()) {
        return $gate_elements;
      }
    }

    $output = parent::viewElements($items, $langcode);
    if ($gate_elements !== []) {
      CacheableMetadata::createFromRenderArray($gate_elements)
        ->applyTo($output);
    }

    /** @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
    $field_definition = $this->fieldDefinition;
    $field_id = $field_definition->id();
    $may_view_hidden_field = $status === CommentItemInterface::HIDDEN;
    $can_render_comments = $this->currentUser->hasPermission('access comments')
      || $this->currentUser->hasPermission('administer comments')
      || $this->currentUser->hasPermission("access {$field_id} comments")
      || $may_view_hidden_field;

    $build_hidden_comment_list = $status === CommentItemInterface::HIDDEN
      && $can_render_comments;

    if ($build_hidden_comment_list) {
      $output[0] ??= [
        '#comment_type' => $this->getFieldSetting('comment_type'),
        '#comment_display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => [],
        'comment_form' => [],
      ];
      $output[0]['comments'] = [];
    }

    if (!empty($entity->id())) {
      $group_contents = GroupRelationship::loadByEntity($entity);
    }

    if (!empty($group_contents)) {
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
      if ($access_view_comments->isForbidden() && !$may_view_hidden_field) {
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

    if (
      (!empty($output[0]['comments']) || $build_hidden_comment_list)
      && !$this->isXmlHttpRequest
    ) {
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

    if (!$this->currentUser->hasPermission('post comments')) {
      $log_in_url = Url::fromRoute('user.login', ['destination' => Url::fromRoute('<current>')->toString() . '#section-comments']);
      $log_in_link = Link::fromTextAndUrl(t('log in'), $log_in_url)
        ->toString();
      $create_account_url = Url::fromRoute('user.register');
      $sign_up = Link::fromTextAndUrl(t('sign up'), $create_account_url)
        ->toString();
      $description = $this->t('Please @log_in or @sign_up to comment.', [
        '@log_in' => $log_in_link,
        '@sign_up' => $sign_up,
      ]);
      $output[0]['comment_form'] = [
        '#prefix' => '<hr>',
        '#markup' => $description,
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

      $membership = $group->getMember($account);
      $this->renderer->addCacheableDependency($output, $membership);

      if ($group->hasPermission($perm, $account)) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }

}
