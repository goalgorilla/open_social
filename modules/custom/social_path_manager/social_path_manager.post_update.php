<?php

/**
 * @file
 * Contains post-update hooks for the Social Path Manager module.
 */

/**
 * Uninstall ctools if nothing uses it.
 */
function social_path_manager_post_update_uninstall_ctools_module() : void {
  // Nothing to do if the module is not installed.
  if (!\Drupal::moduleHandler()->moduleExists("ctools")) {
    return;
  }

  // If there are modules depending on ctools we can't uninstall
  // it.
  if (!empty(\Drupal::service('extension.list.module')->get("ctools")->required_by)) {
    return;
  }

  // Uninstall the module (nothing should depend on it but let the module
  // uninstaller double-check just in case).
  \Drupal::service("module_installer")->uninstall(["ctools"], FALSE);
}
