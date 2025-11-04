<?php

namespace Drupal\social_user_export\Plugin\UserExportPlugin;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'UserPrimaryAffiliationFunction' user export row.
 *
 * @UserExportPlugin(
 *  id = "user_primary_affiliation_function",
 *  label = @Translation("Primary Affiliation Function"),
 *  weight = -310,
 * )
 */
class UserPrimaryAffiliationFunction extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Primary affiliation function');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): MarkupInterface|string {
    $profile = $this->getProfile($entity);

    return $profile instanceof ProfileAffiliationInterface
      ? $profile->getPrimaryAffiliationFunction()
      : '';
  }

}
