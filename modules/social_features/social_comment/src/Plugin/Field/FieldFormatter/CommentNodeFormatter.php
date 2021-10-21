<?php

namespace Drupal\social_comment\Plugin\Field\FieldFormatter;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a node comment formatter.
 *
 * @FieldFormatter(
 *   id = "comment_node",
 *   module = "social_comment",
 *   label = @Translation("Comment on node list"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class CommentNodeFormatter extends SocialCommentFormatterBase {

  /**
   * Newest comments first.
   */
  const ORDER = 'DESC';

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ): self {
    $instance = parent::create($container, $configuration, $plugin_id, $configuration);

    $instance->renderer = $container->get('renderer');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'num_comments' => 2,
      'always_show_all_comments' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $output = [];

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();
    $access_comments_in_group = FALSE;

    // Exclude entities without the set id.
    if ($entity instanceof ContentEntityInterface && !empty($entity->id())) {
      $group_contents = GroupContent::loadByEntity($entity);
    }

    if (!empty($group_contents)) {
      // Add cache contexts.
      $elements['#cache']['contexts'][] = 'group.type';
      $elements['#cache']['contexts'][] = 'user.group_permissions';

      foreach ($group_contents as $group_content) {
        $group = $group_content->getGroup();
        $membership = $group->getMember($this->currentUser);
        $this->renderer->addCacheableDependency($elements, $membership);

        if ($group->hasPermission('access comments', $this->currentUser)) {
          $access_comments_in_group = TRUE;
        }
      }
    }

    $comments_per_page = $this->getSetting('num_comments');

    if (
      $access_comments_in_group &&
      isset($items->status) &&
      $items->status !== CommentItemInterface::HIDDEN &&
      empty($entity->in_preview) &&
      // Comments are added to the search results and search index by
      // comment_node_update_index() instead of by this formatter, so don't
      // return anything if the view mode is search_index or search_result.
      !in_array($this->viewMode, ['search_result', 'search_index']) &&
      isset($entity->get($field_name)->comment_count)
    ) {
      $comment_count = $entity->get($field_name)->comment_count;

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->get($field_name)->comment_count, but unpublished comments
      // should display if the user is an administrator.
      $elements['#cache']['contexts'][] = 'user.permissions';

      if (
        $this->currentUser->hasPermission('access comments') ||
        $this->currentUser->hasPermission('administer comments')
      ) {
        $output['comments'] = [];

        if (
          $comment_count ||
          $this->currentUser->hasPermission('administer comments')
        ) {
          $comment_settings = $this->getFieldSettings();

          $output['comments'] = [
            '#lazy_builder' => [
              'social_comment.lazy_renderer:renderComments',
              [
                $items->getEntity()->getEntityTypeId(),
                $items->getEntity()->id(),
                $comment_settings['default_mode'],
                $items->getName(),
                $comment_settings['per_page'],
                $this->getSetting('pager_id'),
                'default',
                self::ORDER,
                0,
                $this->getBaseId(),
              ],
            ],
            '#create_placeholder' => TRUE,
          ];
        }

        // Prepare the show all comments link.
        $t_args = [':num_comments' => $comment_count];

        // Set link classes to be added to the button.
        $more_link_options = [
          'attributes' => [
            'class' => [
              'btn',
              'btn-flat',
              'brand-text-primary',
            ],
          ],
        ];

        // Set path to node.
        $link_url = $entity->toUrl('canonical');

        // Attach the attributes.
        $link_url->setOptions($more_link_options);

        if ($comment_count == 0) {
          $more_link = $this->t(':num_comments comments', $t_args);
          $output['more_link'] = $more_link;
        }
        elseif ($comment_count == 1) {
          $more_link = $this->t(':num_comments comment', $t_args);
          $output['more_link'] = $more_link;
        }
        else {
          $more_link = $this->t('Show all :num_comments comments', $t_args);
        }

        // Build the link.
        $more_button = Link::fromTextAndUrl($more_link, $link_url);

        if ($this->getSetting('always_show_all_comments') && $comment_count > 1) {
          $output['more_link'] = $more_button;
        }
        elseif ($comments_per_page && $comment_count > $comments_per_page) {
          $output['more_link'] = $more_button;
        }
      }

      $elements[] = $output + [
        '#comment_type' => $this->getFieldSetting('comment_type'),
        '#comment_display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => [],
        'comment_form' => [],
        'more_link' => [],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['num_comments'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 10,
      '#title' => $this->t('Number of comments'),
      '#default_value' => $this->getSetting('num_comments'),
    ];

    $element['always_show_all_comments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show all comments link'),
      '#description' => $this->t('If selected it will show a "all comments" link if there is at least 1 comment.'),
      '#default_value' => $this->getSetting('always_show_all_comments'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function alterQuery(
    SelectInterface $query,
    int $limit,
    string $order = 'ASC'
  ): void {
    parent::alterQuery($query, $limit, self::ORDER);

    $query->isNull('c.pid');
  }

}
