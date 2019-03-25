<?php

namespace Drupal\social_content_report;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\flag\FlagServiceInterface;

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
   * Constructor for ContentReportService.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   Flag service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    FlagServiceInterface $flag_service,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler
  ) {
    $this->flagService = $flag_service;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
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
   */
  public function getModalLink(EntityInterface $entity, $flag_id): ?array {
    // Check if users may flag this entity.
    if (!$this->currentUser->hasPermission('flag ' . $flag_id)) {
      return NULL;
    }

    $flag = $this->flagService->getFlagById($flag_id);
    $flagging = $this->flagService->getFlagging($flag, $entity, $this->currentUser);

    // If the user already flagged this, we return a disabled link to nowhere.
    if ($flagging) {
      return [
        'title' => $this->t('You have reported this'),
        'url' => Url::fromRoute('<none>'),
        'attributes' => [
          'class' => [
            'disabled', 'btn', 'btn-link',
          ],
        ],
      ];
    }

    // Return the modal link if the user did not yet flag this content.
    return [
      'title' => $this->t('Report'),
      'url' => Url::fromRoute('flag.field_entry',
        [
          'flag' => $flag_id,
          'entity_id' => $entity->id(),
        ],
        [
          'query' => [
            'destination' => Url::fromRoute('<current>')->toString(),
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
