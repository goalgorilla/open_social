<?php

namespace Drupal\social_comment\Form;

use Drupal\comment\CommentInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides the comments overview administration form.
 *
 * @internal
 */
class SocialCommentAdminOverview extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The comment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Creates a CommentAdminOverview form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler, PrivateTempStoreFactory $temp_store_factory, Renderer $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->commentStorage = $entity_type_manager->getStorage('comment');
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
    $this->tempStoreFactory = $temp_store_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('module_handler'),
      $container->get('tempstore.private'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_admin_overview';
  }

  /**
   * Form constructor for the comment overview administration form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $type
   *   The type of the overview form ('approval' or 'new').
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'new') {

    // Build an 'Update options' form.
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];

    if ($type == 'approval') {
      $options['publish'] = $this->t('Publish the selected comments');
    }
    else {
      $options['unpublish'] = $this->t('Unpublish the selected comments');
    }
    $options['delete'] = $this->t('Delete the selected comments');

    $form['options']['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'publish',
    ];
    $form['options']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    // Load the comments that need to be displayed.
    $status = ($type == 'approval') ? CommentInterface::NOT_PUBLISHED : CommentInterface::PUBLISHED;
    $header = [
      'author' => [
        'data' => $this->t('Author'),
        'specifier' => 'name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'comment' => [
        'data' => $this->t('Comment'),
        'specifier' => 'comment_body',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'changed' => [
        'data' => $this->t('Updated'),
        'specifier' => 'changed',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'operations' => $this->t('Operations'),
    ];
    $cids = $this->commentStorage->getQuery()
      ->condition('status', $status)
      ->tableSort($header)
      ->pager(50)
      ->execute();

    /** @var \Drupal\comment\CommentInterface[] $comments */
    $comments = $this->commentStorage->loadMultiple($cids);

    // Build a table listing the appropriate comments.
    $options = [];
    $destination = $this->getDestinationArray();

    foreach ($comments as $comment) {
      // Get a render array for the comment body field. We'll render it in the
      // table.
      $comment_body = $comment->field_comment_body->view('full');

      $options[$comment->id()] = [
        'title' => ['data' => ['#title' => $comment->getSubject() ?: $comment->id()]],
        'author' => [
          'data' => [
            '#theme' => 'username',
            '#account' => $comment->getOwner(),
          ],
        ],
        'comment' => [
          'data' => [
            '#markup' => $this->renderer->renderRoot($comment_body),
          ],
        ],
        'changed' => $this->dateFormatter->format($comment->getChangedTimeAcrossTranslations(), 'short'),
      ];

      // Create a list of operations.
      $comment_uri_options = $comment->toUrl()->getOptions() + ['query' => $destination];

      $links['view'] = [
        'title' => $this->t('View'),
        'url' => $comment->toUrl(),
      ];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => $comment->toUrl('edit-form', $comment_uri_options),
      ];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => $comment->toUrl('delete-form', $comment_uri_options),
      ];

      // Add a 'Translate' operations link if the comment is translatable.
      if ($this->moduleHandler->moduleExists('content_translation') &&
        $comment->getCommentedEntity() instanceof ContentEntityInterface &&
        $this->moduleHandler->invoke('content_translation', 'translate_access', [$comment])->isAllowed()) {
        $links['translate'] = [
          'title' => $this->t('Translate'),
          'url' => $comment->toUrl('drupal:content-translation-overview', $comment_uri_options),
        ];
      }

      $options[$comment->id()]['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    $form['comments'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No comments available.'),
    ];

    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('comments', array_diff($form_state->getValue('comments'), [0]));
    // We can't execute any 'Update options' if no comments were selected.
    if (count($form_state->getValue('comments')) == 0) {
      $form_state->setErrorByName('', $this->t('Select one or more comments to perform the update on.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    $cids = $form_state->getValue('comments');
    /** @var \Drupal\comment\CommentInterface[] $comments */
    $comments = $this->commentStorage->loadMultiple($cids);
    if ($operation != 'delete') {
      foreach ($comments as $comment) {
        if ($operation == 'unpublish') {
          $comment->setUnpublished();
        }
        elseif ($operation == 'publish') {
          $comment->setPublished();
        }
        $comment->save();
      }
      $this->messenger()->addStatus($this->t('The update has been performed.'));
      $form_state->setRedirect('comment.admin');
    }
    else {
      $info = [];
      /** @var \Drupal\comment\CommentInterface $comment */
      foreach ($comments as $comment) {
        $langcode = $comment->language()->getId();
        $info[$comment->id()][$langcode] = $langcode;
      }
      $this->tempStoreFactory
        ->get('comment_multiple_delete_confirm')
        ->set($this->currentUser()->id(), $info);
      $form_state->setRedirect('comment.multiple_delete_confirm');
    }
  }

}
