<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerInterface.
 */

namespace Drupal\group\Plugin;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for pluggable GroupContentEnabler back-ends.
 *
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\GroupContentEnablerManager
 * @see \Drupal\group\Plugin\GroupContentEnablerBase
 * @see plugin_api
 */
interface GroupContentEnablerInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the plugin provider.
   *
   * @return string
   */
  public function getProvider();

  /**
   * Returns the administrative label for the plugin.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the administrative description for the plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Returns the entity type ID the plugin supports.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns the entity bundle the plugin supports.
   *
   * @return string|false
   *   The bundle name or FALSE in case it supports all bundles.
   */
  public function getEntityBundle();

  /**
   * Returns the amount of groups the same content can be added to.
   *
   * @return int
   *   The group content's group cardinality.
   */
  public function getGroupCardinality();

  /**
   * Returns the amount of times the same content can be added to a group.
   *
   * @return int
   *   The group content's entity cardinality.
   */
  public function getEntityCardinality();

  /**
   * Returns the group type the plugin was instantiated for.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface|null
   *   The group type, if set in the plugin configuration.
   */
  public function getGroupType();

  /**
   * Returns the ID of the group type the plugin was instantiated for.
   *
   * @return string|null
   *   The group type ID, if set in the plugin configuration.
   */
  public function getGroupTypeId();

  /**
   * Returns whether this plugin is always on.
   *
   * @return bool
   *   The 'enforced' status.
   */
  public function isEnforced();

  /**
   * Retrieves the label for a piece of group content.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *
   * @return string
   *   The label as expected by \Drupal\Core\Entity\EntityInterface::label().
   */
  public function getContentLabel(GroupContentInterface $group_content);

  /**
   * Returns a safe, unique configuration ID for a group content type.
   *
   * By default we use GROUP_TYPE_ID.PLUGIN_ID.DERIVATIVE_ID, but feel free to
   * use any other means of identifying group content types. Make sure you also
   * provide a configuration schema should you diverge from the default
   * group.content_type.*.* or group_content_type.*.*.* schema.
   *
   * Please do not return any invalid characters in the ID as it will crash the
   * website. Refer to ConfigBase::validateName() for valid characters.

   * @return string
   *   The safe ID to use as the configuration name.
   *
   * @see \Drupal\Core\Config\ConfigBase::validateName()
   */
  public function getContentTypeConfigId();

  /**
   * Returns the administrative label for a group content type.
   *
   * @return string
   */
  public function getContentTypeLabel();

  /**
   * Returns the administrative description for a group content type.
   *
   * @return string
   */
  public function getContentTypeDescription();

  /**
   * Provides an list of operations for a group.
   *
   * These operations can be implemented in numerous ways by extending modules.
   * Out of the box, Group provides a block that shows the available operations
   * to a user visiting a route with a group in its URL.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to generate the operations for.
   *
   * @return array
   *   An associative array of operation links to show when in a group context,
   *   keyed by operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of the operation.
   */
  public function getGroupOperations(GroupInterface $group);

  /**
   * Provides an list of operations for the content enabler plugin.
   *
   * These operations will be merged with the ones already available on the
   * group type content configuration page: (un)install, manage fields, etc.
   *
   * @return array
   *   An associative array of operation links to show on the group type content
   *   administration UI, keyed by operation name, containing the following
   *   key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  public function getOperations();

  /**
   * Provides a list of additional forms to enable for group content entities.
   *
   * Usually, entity types only specify three forms in their annotation: 'add',
   * 'edit' and 'delete'. In case you want to extend one of these forms to work
   * some magic on them, you may need to define an extra key by altering the
   * GroupContent entity type definition.
   *
   * To avoid multiple modules messing with the entity type definition, Group
   * allows content enabler plugins to define extra forms on the plugin. A good
   * example is \Drupal\group\Plugin\GroupContentEnabler\GroupMembership.
   *
   * Please prefix your form names with your module's name to avoid collisions
   * with other modules' forms. E.g.: group-join, group-leave, ... instead of
   * join, leave, ...
   *
   * @return array
   *   An associative array as seen in the 'form' section of the 'handlers' key
   *   in an entity annotation. I.e.: An array where the keys represent form
   *   names and the values are class names.
   */
  public function getEntityForms();

  /**
   * Provides a list of group permissions the plugin exposes.
   *
   * If you have some group permissions that would only make sense when your
   * plugin is installed, you may define those here. They will not be shown on
   * the permission configuration form unless the plugin is installed.
   *
   * @return array
   *   An array of group permissions, see GroupPermissionHandlerInterface for
   *   the structure of a group permission.
   *
   * @see GroupPermissionHandlerInterface::getPermissions()
   */
  public function getPermissions();

  /**
   * Returns all paths defined by the plugin.
   *
   * @return string[]
   *   An associative array of paths, keyed by their link template name (e.g.:
   *   canonical, add-form, ...).
   */
  public function getPaths();

  /**
   * Returns a path defined by the plugin.
   *
   * @var string $name
   *   The link template name to retrieve the path for.
   *
   * @return string|false
   *   The path for the provided link template or FALSE if none was found.
   */
  public function getPath($name);

  /**
   * Returns a route name defined by the plugin.
   *
   * @var string $name
   *   The link template name to retrieve the route name for.
   *
   * @return string
   *   The route name for the provided link template.
   */
  public function getRouteName($name);

  /**
   * Provides routes for GroupContent entities.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of routes keyed by name.
   */
  public function getRoutes();

  /**
   * Provides local actions for group content routes.
   *
   * @return array
   *   An array of local action definitions, keyed by a unique ID. Please try to
   *   namespace your IDs to avoid collisions with other plugins' local actions.
   *
   * @see \Drupal\group\Plugin\GroupContentEnablerBase::getLocalActions()
   * @see https://www.drupal.org/node/2133247
   */
  public function getLocalActions();

  /**
   * Performs access check for the create operation.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check for content creation access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function createAccess(GroupInterface $group, AccountInterface $account);

  /**
   * Checks access to an operation on a given group content entity.
   *
   * Use \Drupal\group\Plugin\GroupContentEnablerInterface::createAccess() to
   * check access to create a group content entity.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content for which to check access.
   * @param string $operation
   *   The operation access should be checked for. Usually one of "view",
   *   "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(GroupContentInterface $group_content, $operation, AccountInterface $account);

  /**
   * Returns a list of entity reference field settings.
   *
   * This allows you to provide some handler settings for the entity reference
   * field referencing the entity that is to become group content. You could
   * even change the handler being used, all without having to alter the bundle
   * field settings yourself through an alter hook.
   *
   * @return array
   *   An associative array where the keys are valid entity reference field
   *   setting names and the values are the corresponding setting for each key.
   *   Often used keys are 'target_type', 'handler' and 'handler_settings'.
   */
  public function getEntityReferenceSettings();

  /**
   * Runs tasks after the group content type for this plugin has been created.
   *
   * A good example of what you might want to do here, is the installation of
   * extra locked fields on the group content type. You can find an example in
   * \Drupal\group\Plugin\GroupContentEnabler\GroupMembership::postInstall().
   */
  public function postInstall();

}
