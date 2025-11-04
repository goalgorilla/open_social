<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserPrimaryAffiliationType' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_primary_affiliation_type",
 *  label = @Translation("Primary affiliation type"),
 *  weight = -320,
 * )
 */
class UserPrimaryAffiliationType extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Primary affiliation type');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): MarkupInterface|string {
    $profile = $this->getProfile($entity);

    return $profile instanceof ProfileAffiliationInterface
      ? $profile->getPrimaryAffiliationType()
      : '';
  }

}
