<?php

/**
 * @file
 * Hooks provided by the Social Event module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the default event enrollment role.
 *
 * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $role_name
 *   The role name.
 * @param \Drupal\views\ResultRow $row
 *   The values retrieved from a single row of a view's query result.
 *
 * @ingroup social_event_api
 */
function hook_social_event_role_alter(&$role_name, \Drupal\views\ResultRow $row) {
  if (!isset($row->_relationship_entities->user)) {
    $role_name = t('Guest');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
