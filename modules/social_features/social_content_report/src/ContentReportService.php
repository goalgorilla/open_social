<?php

namespace Drupal\social_content_report;

use Drupal\comment\CommentInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a content report service.
 */
class ContentReportService implements ContentReportServiceInterface {

  use StringTranslationTrait;

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor for ContentReportService.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   Flag service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    FlagServiceInterface $flag_service,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler,
    RequestStack $requestStack
  ) {
    $this->flagService = $flag_service;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function getReportFlagTypes(): array {
    $report_flags = $this->moduleHandler->invokeAll('social_content_report_flags');

    // Allow using reports for three predefined entity types.
    $report_flags = array_merge($report_flags, [
      'report_comment',
      'report_node',
      'report_post',
    ]);

    $this->moduleHandler->alter('social_content_report_flags', $report_flags);

    return $report_flags;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getModalLink(EntityInterface $entity, $flag_id, $is_button = FALSE): ?array {
    // Check if users may flag this entity.
    if (!$this->currentUser->hasPermission('flag ' . $flag_id)) {
      return NULL;
    }

    $flagging = FALSE;
    $flag = $this->flagService->getFlagById($flag_id);

    if ($flag !== NULL) {
      $flagging = $this->flagService->getFlagging($flag, $entity, $this->currentUser);
    }

    // If the user already flagged this, we return a disabled link to nowhere.
    if ($flagging) {
      $element = [
        'title' => $this->t('Reported'),
        'attributes' => [
          'class' => [
            'disabled',
          ],
        ],
      ];

      if ($is_button) {
        $element += [
          'url' => Url::fromRoute('<none>'),
          'attributes' => [
            'class' => [
              'btn',
              'btn-link',
            ],
          ],
        ];
      }

      return $element;
    }

    // Return the modal link if the user did not yet flag this content.
    $currentUrl = Url::fromRoute('<current>')->toString();

    $currentRequest = $this->requestStack->getCurrentRequest();
    // If there's a request and it's an ajax request, we need to do something
    // different. Current url will now be determined based on something else.
    if ($entity instanceof CommentInterface && $currentRequest !== NULL && $currentRequest->isXmlHttpRequest() === TRUE) {
      // Determine the parent entity, so we can redirect to the entity
      // the comment was added to.
      $parentEntity = $entity->getCommentedEntity();

      if ($parentEntity !== NULL) {
        $currentUrl = $parentEntity->toUrl()->toString();
      }
    }

    /** @var \Drupal\comment\Entity\Comment $entity */
    return [
      'title' => $this->t('Report'),
      'url' => Url::fromRoute('flag.field_entry',
        [
          'flag' => $flag_id,
          'entity_id' => $entity->id(),
        ],
        [
          'query' => [
            'destination' => $currentUrl,
          ],
        ]
      ),
      'attributes' => [
        'data-dialog-type' => 'modal',
        'data-dialog-options' => JSON::encode([
          'width' => 400,
          'dialogClass' => 'content-reporting-dialog',
        ]),
        'class' => ['use-ajax', 'content-reporting-link'],
      ],
    ];
  }

}
