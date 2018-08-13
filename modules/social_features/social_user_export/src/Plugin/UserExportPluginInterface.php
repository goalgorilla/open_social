<?php

namespace Drupal\social_user_export\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Defines an interface for User export plugin plugins.
 */
interface UserExportPluginInterface extends PluginInspectionInterface {

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader();

  /**
   * Returns the value.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The User entity to get the value from.
   *
   * @return string
   *   The value.
   */
  public function getValue(UserInterface $entity);

  /**
   * Get the Profile entity.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The user entity to get the profile from.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   Returns the Profile or NULL if profile does not exist.
   */
  public function getProfile(UserInterface $entity);

  /**
   * Returns the value of a field for a given profile.
   *
   * @param string $field_name
   *   The field name to get the value for.
   * @param \Drupal\profile\Entity\ProfileInterface $user_profile
   *   The profile to get the data for.
   *
   * @return string
   *   Returns value of the field.
   */
  public function profileGetFieldValue($field_name, ProfileInterface $user_profile = NULL);

  /**
   * Returns the value for the address field and element within address.
   *
   * @param string $field_name
   *   The field name to get the value for.
   * @param string $address_element
   *   The address element to get the value for, e.g. 'country_code'.
   * @param \Drupal\profile\Entity\ProfileInterface $user_profile
   *   The profile to get the data for.
   *
   * @return string
   *   Returns the value of the address element for the profile.
   */
  public function profileGetAddressFieldValue($field_name, $address_element, ProfileInterface $user_profile = NULL);

  /**
   * Returns the values of a taxonomy reference field.
   *
   * @param string $field_name
   *   The field name to get the value for, should be taxonomy term reference.
   * @param \Drupal\profile\Entity\ProfileInterface $user_profile
   *   The profile to get the data for.
   *
   * @return string
   *   Returns comma separated string of taxonomy terms of the field.
   */
  public function profileGetTaxonomyFieldValue($field_name, ProfileInterface $user_profile = NULL);

}
