<?php

namespace Drupal\social_content_report;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\flag\Entity\Flag;
use Drupal\flag\FlagServiceInterface;

/**
 * Provides a content report service.
 */
class ContentReportService {

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
   * Constructor for ContentReportService.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   Flag service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   */
  public function __construct(FlagServiceInterface $flag_service, AccountProxyInterface $current_user) {
    $this->flagService = $flag_service;
    $this->currentUser = $current_user;
  }

  /**
   * Gets all the 'report_' flag types.
   *
   * This makes it more flexible so when new flags are
   * added, it automatically gets them as well.
   *
   * @return array
   *   List of flag type IDs that are used for reporting.
   */
  public function getReportFlagTypes() {
    $all_flags = Flag::loadMultiple();
    $report_flags = [];
    if (!empty($all_flags)) {
      // Check if this is a report flag.
      foreach ($all_flags as $flag) {
        if (strpos($flag->id, 'report_') === 0) {
          $report_flags[] = $flag->id;
        }
      }
    }

    return $report_flags;
  }

  /**
   * Returns a modal link to the reporting form to use in a #links array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to create the report for.
   * @param string $flag_id
   *   The flag ID.
   *
   * @return array
   *   A renderable array to be used in a #links array.
   */
  public function getModalLink(EntityInterface $entity, $flag_id) {
    // Check if users may flag this entity.
    if (!$this->currentUser->hasPermission('flag ' . $flag_id)) {
      return FALSE;
    }

    $flag = $this->flagService->getFlagById($flag_id);
    $flagging = $this->flagService->getFlagging($flag, $entity, $this->currentUser);

    // If the user already flagged this, then we can skip rendering a link.
    if ($flagging) {
      return FALSE;
    }

    // Return the modal link.
    return [
      'title' => $this->t('Report'),
      'url' => Url::fromRoute('flag.field_entry',
        [
          'flag' => $flag_id,
          'entity_id' => $entity->id(),
        ],
        ['query' => ['destination' => Url::fromRoute('<current>')->toString()]]),
      'attributes' => [
        'data-dialog-type' => 'modal',
        'class' => ['use-ajax'],
      ],
    ];
  }

}
