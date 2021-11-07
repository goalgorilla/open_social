<?php

namespace Drupal\social_profile_organization_tag\Plugin\UserExportPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_user_export\Plugin\UserExportPluginBase;
use Drupal\user\UserInterface;

/**
 * Provides a 'OrganizationTag' user export row.
 *
 * @UserExportPlugin(
 *  id = "organization_tag",
 *  label = @Translation("Organization Tag"),
 *  weight = -305,
 * )
 */
class OrganizationTag extends UserExportPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getHeader(): TranslatableMarkup {
    return $this->t('Organization Tag');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity): string {
    return $this->profileGetTaxonomyFieldValue('field_profile_organization_tag', $this->getProfile($entity));
  }

}
