<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Action;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_profile\GroupAffiliation;
use Drupal\social_profile\TestControlInterface\Success\AffiliationConfiguredSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoints for affiliation configuration (replaces AffiliationContext).
 */
final class ManageAffiliationConfig {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Enables or disables the affiliation feature flag for the platform.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationConfiguredSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'affiliation_platform_toggle',
    patterns: ['I :action affiliations on platform'],
    type: OperationType::Write,
  )]
  public function toggleAffiliations(
    #[Example('enable')]
    string $action,
  ): AffiliationConfiguredSuccess {
    if (!in_array($action, ['enable', 'disable'], TRUE)) {
      throw new BadRequestHttpException(sprintf("Invalid action '%s'. Must be 'enable' or 'disable'.", $action));
    }

    $this->configFactory->getEditable('social_profile.settings')
      ->set('group_affiliation_status', $action === 'enable')
      ->save();

    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    return new AffiliationConfiguredSuccess();
  }

  /**
   * Enables affiliation for a given group type.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationConfiguredSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'affiliation_group_type_enable',
    patterns: ['I enable affiliation for :groupTypeLabel'],
    type: OperationType::Write,
  )]
  public function enableAffiliationForGroupType(
    #[Example('flexible_group')]
    string $groupTypeLabel,
  ): AffiliationConfiguredSuccess {
    $groupTypes = $this->entityTypeManager->getStorage('group_type')->loadMultiple();

    $matchedType = NULL;
    foreach ($groupTypes as $groupType) {
      if ($groupType->label() === $groupTypeLabel) {
        $matchedType = $groupType;
        break;
      }
    }
    if ($matchedType === NULL) {
      throw new BadRequestHttpException(sprintf("Group type with label '%s' not found.", $groupTypeLabel));
    }

    $config = $this->configFactory->getEditable('group.type.' . $matchedType->id());
    $settings = $config->get('third_party_settings') ?? [];
    $settings['social_profile'][GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY] = TRUE;
    $settings['social_profile'][GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY] = TRUE;
    $config->set('third_party_settings', $settings)->save();

    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    return new AffiliationConfiguredSuccess();
  }

  /**
   * Configures an automatic affiliation rule for a group type.
   *
   * @return \Drupal\social_profile\TestControlInterface\Success\AffiliationConfiguredSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'affiliation_automatic_rule_configure',
    patterns: ['I configure automatic affiliation rule for :groupType with retroactive :retroactive'],
    type: OperationType::Write,
  )]
  public function configureAutomaticAffiliationRule(
    #[Example('flexible_group')]
    string $groupType,
    #[Example('true')]
    string $retroactive,
  ): AffiliationConfiguredSuccess {
    if (!in_array($retroactive, ['true', 'false'], TRUE)) {
      throw new BadRequestHttpException(sprintf("Invalid retroactive value '%s'. Must be 'true' or 'false'.", $retroactive));
    }

    if ($this->entityTypeManager->getStorage('group_type')->load($groupType) === NULL) {
      throw new BadRequestHttpException(sprintf("Group type '%s' not found.", $groupType));
    }

    $config = $this->configFactory->getEditable('social_profile.automatic_group_affiliations.profile');
    $rules = $config->get('rules') ?? [];

    $ruleExists = FALSE;
    foreach ($rules as &$rule) {
      foreach ($rule['selectors'] ?? [] as $selector) {
        if (($selector['type'] ?? '') === $groupType) {
          $rule['retroactive'] = $retroactive === 'true';
          $ruleExists = TRUE;
          break 2;
        }
      }
    }
    unset($rule);

    if (!$ruleExists) {
      $rules[] = [
        'weight' => 0,
        'sort' => [
          'by' => 'created',
          'direction' => 'ASC',
        ],
        'retroactive' => $retroactive === 'true',
        'selectors' => [
          ['type' => $groupType],
        ],
      ];
    }

    $config->set('rules', $rules)->save();

    Cache::invalidateTags([
      GroupAffiliation::GENERAL_CACHE_TAG,
      'config:social_profile.automatic_group_affiliations.profile',
    ]);

    return new AffiliationConfiguredSuccess();
  }

}
