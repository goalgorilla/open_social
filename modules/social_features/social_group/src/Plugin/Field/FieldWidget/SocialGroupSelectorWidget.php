<?php

namespace Drupal\social_group\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\select2\Plugin\Field\FieldWidget\Select2EntityReferenceWidget;
use Drupal\social_group\GroupRelationshipStanding;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A widget to select a group when creating an entity in a group.
 *
 * @FieldWidget(
 *   id = "social_group_selector_widget",
 *   label = @Translation("Social group select list"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SocialGroupSelectorWidget extends Select2EntityReferenceWidget {

  use StringTranslationTrait;

  /**
   * The list of options for the widget.
   */
  protected array $options;

  /**
   * IDs of the entity's groups the current user is not allowed to detach.
   */
  protected array $lockedGroupIds = [];

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The plugin manager.
   */
  protected GroupRelationTypeManagerInterface $pluginManager;

  /**
   * The user entity storage.
   */
  protected UserStorageInterface $userManager;

  /**
   * The single owner of the group relationship standing rule.
   */
  protected GroupRelationshipStanding $groupRelationshipStanding;

  /**
   * Creates a SocialGroupSelectorWidget instance.
   *
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ConfigFactoryInterface $configFactory,
    AccountInterface $currentUser,
    ModuleHandlerInterface $moduleHandler,
    GroupRelationTypeManagerInterface $pluginManager,
    EntityTypeManagerInterface $entity_type_manager,
    GroupRelationshipStanding $group_relationship_standing,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $currentUser;
    $this->pluginManager = $pluginManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->userManager = $entity_type_manager->getStorage('user');
    $this->groupRelationshipStanding = $group_relationship_standing;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('group_relation_type.manager'),
      $container->get('entity_type.manager'),
      $container->get('social_group.group_relationship_standing')
    );
  }

  /**
   * Gets a list of supported entity types.
   */
  protected function types(): array {
    return ['node'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity): array {
    if (!in_array($type = $entity->getEntityTypeId(), $this->types())) {
      return parent::getOptions($entity);
    }

    /** @var \Drupal\user\Entity\User $account */
    $account = $this->userManager->load($this->currentUser->id());

    $groupsAdmin = $account->hasPermission('manage all groups');

    // The options have to be built for the editor rather than the owner: an
    // option this widget does not carry is never submitted, and the save
    // reconciles the relationships against the submitted value (PROD-36392).
    $optionsProvider = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getOptionsProvider($this->column, $entity);

    $storage = $this->entityTypeManager->getStorage('group');

    if ($optionsProvider !== NULL) {
      $options = $optionsProvider->getSettableOptions($account);

      // Check for each group type if the content type is installed.
      foreach ($options as $key => $optgroup) {
        // Groups are in the array below.
        if (is_array($optgroup)) {
          $group = $storage->load(array_keys($optgroup)[0]);

          // If the group exists.
          if ($group instanceof GroupInterface) {
            $supported = $this->pluginManager
              ->getInstalled($group->getGroupType())
              ->has('group_' . $type . ':' . $entity->bundle());

            // If the bundle is not installed,
            // then unset the entire option group (=group type).
            if (!$supported) {
              unset($options[$key]);
            }
          }
        }
      }

      // Remove groups the user does not have creation access to.
      if (!$groupsAdmin) {
        $options = $this->removeGroupsWithoutCreateAccess(
          $options,
          $account,
          $entity,
        );
      }
    }
    else {
      $options = [];
    }

    // Add an empty option if the widget needs one.
    if ($emptyLabel = $this->getEmptyLabel()) {
      $options = ['_none' => $emptyLabel] + $options;
    }

    $context = [
      'fieldDefinition' => $this->fieldDefinition,
      'entity' => $entity,
    ];

    $this->moduleHandler->alter('options_list', $options, $context);

    array_walk_recursive($options, [$this, 'sanitizeLabel']);

    // Set required property for the current object.
    // @todo @: Should be removed after https://www.drupal.org/files/issues/2923353-5.patch will be merged in the core.
    /* @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase::getOptions() */
    if (!isset($this->options)) {
      $this->options = $options ?? parent::getOptions($entity);
    }

    // The entity can already be in groups that are not offered here, because
    // the editor left the group or was never in it. Those groups are added back
    // to the list: an option the widget does not carry is not submitted, and
    // saving that shorter value is exactly what drops content out of its group.
    // They are deliberately added after hook_options_list_alter() - a group an
    // alter put back is already selectable - and after $this->options is kept,
    // because isMultipleSelectionAvailable() may only count what this editor
    // can pick.
    $relationships = $this->currentGroupRelationships($entity);

    // Which of them the editor may not detach is decided by the same rule the
    // save applies, not by their absence from the list above. Deriving the lock
    // from the option list instead would disable - and therefore drop - groups
    // that SocialGroupReferenceItemList::postSave() does not put back, for
    // example for an account holding the entity admin permission.
    $this->lockedGroupIds = array_map(
      'intval',
      array_values($this->groupRelationshipStanding->lockedGroupIds($entity, $relationships)),
    );

    $missing = array_diff(
      array_keys($relationships),
      array_map('intval', array_keys(OptGroup::flattenOptions($options))),
    );

    // Locked groups go first, so the last of the preselected options is always
    // one the editor can act on: on a single-value select every group the
    // entity is in is preselected and the browser keeps only the last of them.
    $missing = array_merge(
      array_intersect($missing, $this->lockedGroupIds),
      array_diff($missing, $this->lockedGroupIds),
    );

    foreach (array_reverse($missing) as $gid) {
      $group = $storage->load($gid);

      if (!$group instanceof GroupInterface) {
        continue;
      }

      $label = (string) $group->label();
      $this->sanitizeLabel($label);

      $options = [$gid => $label] + $options;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#suffix'] = '<div id="group-selection-result"></div>';
    $element['#ajax'] = [
      'callback' => __CLASS__ . '::validateGroupSelection',
      'effect' => 'fade',
      'event' => 'change',
    ];

    // Unfortunately, validateGroupSelection is cast as a static function,
    // So I have to add this setting to the form to use it later on.
    $defaultVisibility = $this->configFactory->get('entity_access_by_field.settings')
      ->get('defaultVisibility');

    $form['defaultVisibility'] = [
      '#type' => 'value',
      '#value' => $defaultVisibility,
    ];

    $element['#multiple'] = $this->isMultipleSelectionAvailable($items);

    $socialGroupSettings = $this->configFactory->get('social_group.settings');
    $changeGroupNode = $socialGroupSettings->get('allow_group_selection_in_node');

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    $entity = $form_object->getEntity();

    // If it is a new node, let's add the current group.
    if (!$entity->id()) {
      $currentGroup = _social_group_get_current_group();
      if ($currentGroup !== NULL && empty($element['#default_value'])) {
        $element['#default_value'] = [$currentGroup->id()];
      }
    }
    else {
      if (!$changeGroupNode && !$this->currentUser->hasPermission('manage all groups')) {
        $element['#disabled'] = TRUE;
        $element['#description'] = $this->t('Moving content after creation function has been disabled. In order to move this content, please contact a site manager.');
      }
    }

    // We don't allow to LU to edit field if there are multiple values. Locked
    // groups do not count: the editor cannot act on them anyway, and letting
    // them trip this check would take away the one group the editor does have
    // standing in.
    $editableDefaults = array_diff($element['#default_value'], $this->lockedGroupIds);

    if (count($editableDefaults) > 1) {
      if ($socialGroupSettings->get('cross_posting.status') && !$this->currentUser->hasPermission('access cross-group posting')) {
        $element['#disabled'] = TRUE;
        $element['#description'] = $this->t('You are not allowed to edit this field!');
      }
    }

    // Show the groups this editor may not act on as locked. getOptions()
    // collected them for $items->getEntity() while the parent built the
    // element, so they are right for embedded widgets too. This is
    // presentation only, and only in themes that render #options_attributes
    // (socialbase does, Claro does not): the enforcement lives in
    // SocialGroupReferenceItemList::postSave(), which recomputes the same rule
    // from stored state on every entity save.
    foreach ($this->lockedGroupIds as $gid) {
      $element['#options_attributes'][$gid]['disabled'] = 'disabled';
    }

    return $element;
  }

  /**
   * Validate the group selection and change the visibility settings.
   *
   * @param array $form
   *   Form to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state to process.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response changing values of the visibility field and set status message.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function validateGroupSelection(array $form, FormStateInterface $form_state): AjaxResponse {
    $ajaxResponse = new AjaxResponse();

    /** @var \Drupal\Core\Entity\EntityFormInterface $formObject */
    $formObject = $form_state->getFormObject();

    $entity = $formObject->getEntity();

    $selectedVisibility = $form_state->getValue('field_content_visibility');
    if (!empty($selectedVisibility)) {
      $selectedVisibility = $selectedVisibility['0']['value'];
    }
    if ($selectedGroups = $form_state->getValue('groups')) {
      $allowedVisibilityOptions = self::getVisibilityOptionsForMultipleGroups(array_column($selectedGroups, 'target_id'), $entity);
    }
    else {
      $defaultVisibility = $form_state->getValue('defaultVisibility');

      $allowedVisibilityOptions = social_group_get_allowed_visibility_options_per_group_type(NULL, NULL, $entity);
      // Drupal selectors don't use underscores, but hyphens.
      $defaultVisibility = str_replace('_', '-', $defaultVisibility);
      $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $defaultVisibility, 'prop', ['checked', 'checked']));
    }

    foreach ($allowedVisibilityOptions as $visibility => $allowed) {
      // Drupal selectors don't use underscores, but hyphens.
      $visibility = str_replace('_', '-', $visibility);
      $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'addClass', ['js--animate-enabled-form-control']));
      if ($allowed === TRUE) {
        $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'removeAttr', ['disabled']));
        if (empty($defaultVisibility) || $visibility === $defaultVisibility) {
          $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'prop', ['checked', 'checked']));
        }
      }
      else {
        if ($selectedVisibility && $selectedVisibility === $visibility) {
          $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'removeAttr', ['checked']));
        }
        $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'prop', ['disabled', 'disabled']));
      }

      $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $visibility, 'change'));
    }

    $text = t('Changing the group may have an impact on the <strong>visibility settings</strong> and may cause <strong>author/co-authors</strong> to lose access.');

    \Drupal::messenger()->addStatus($text);

    return $ajaxResponse->addCommand(
      new HtmlCommand('#group-selection-result', $text),
    );
  }

  /**
   * Get content visibility options for multiple groups.
   *
   *  If there are a few groups, a user should be able to add visibility options
   *  only if the groups have at least one shared option.
   *  F.e, if "Open Group" has only the "Public" option and "Secret Group" have
   *  "Only group members" option, then a user should not be able to save
   *  the entity (because of an error).
   *
   * @param array $groupIds
   *   A list of groups ids.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private static function getVisibilityOptionsForMultipleGroups(
    array $groupIds,
    EntityInterface $entity,
  ): array {
    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = \Drupal::entityTypeManager()->getStorage('group')
      ->loadMultiple($groupIds);

    return social_group_get_allowed_content_visibility_options_for_multiple_groups($groups, $entity);
  }

  /**
   * Get the group relationships the entity currently has, keyed by group ID.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity the widget edits.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface[]
   *   The relationships, read from storage rather than from the field value, so
   *   a tampered submission cannot influence them.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function currentGroupRelationships(FieldableEntityInterface $entity): array {
    if (!$entity instanceof ContentEntityInterface || $entity->id() === NULL) {
      return [];
    }

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content');

    $relationships = [];

    foreach ($storage->loadByEntity($entity) as $relationship) {
      $relationships[(int) $relationship->getGroupId()] = $relationship;
    }

    return $relationships;
  }

  /**
   * Remove options from the list.
   *
   * @param array $options
   *   A list of options to check.
   * @param \Drupal\user\UserInterface $account
   *   The user is to check for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for.
   *
   * @return array
   *   A list of options for the field containing groups with creation access.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function removeGroupsWithoutCreateAccess(
    array $options,
    UserInterface $account,
    EntityInterface $entity,
  ): array {
    foreach ($options as $optionCategoryKey => $groupsInCategory) {
      if (is_array($groupsInCategory)) {
        foreach (array_keys($groupsInCategory) as $gid) {
          if (!$this->checkGroupContentCreateAccess($gid, $account, $entity)) {
            unset($options[$optionCategoryKey][$gid]);
          }
        }
        // Remove the entire category if there are no groups for this author.
        if (empty($options[$optionCategoryKey])) {
          unset($options[$optionCategoryKey]);
        }
      }
      else {
        if (!$this->checkGroupContentCreateAccess($optionCategoryKey, $account, $entity)) {
          unset($options[$optionCategoryKey]);
        }
      }
    }

    return $options;
  }

  /**
   * Check if a user may create content of a bundle in a group.
   *
   * @param int $gid
   *   Group id.
   * @param \Drupal\user\UserInterface $account
   *   The user is to check for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node bundle to check for.
   *
   * @return bool
   *   TRUE if the user has permission to create the entity in the group.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function checkGroupContentCreateAccess(
    int $gid,
    UserInterface $account,
    EntityInterface $entity,
  ): bool {
    $group = $this->entityTypeManager->getStorage('group')->load($gid);

    return $group instanceof GroupInterface &&
      $group->hasPermission(
        sprintf(
          'create group_%s:%s entity',
          $entity->getEntityTypeId(),
          $entity->bundle(),
        ),
        $account,
      );
  }

  /**
   * Disable multiple selection based on cross-posting settings.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<\Drupal\group\Entity\Group>> $items
   *   The field item list interface.
   *
   * @return bool
   *   TRUE if multiple selection should be disabled, FALSE otherwise.
   */
  private function disableMultipleSelection(FieldItemListInterface $items): bool {
    $socialGroupSettings = $this->configFactory->get('social_group.settings');

    $hasPermission = $this->currentUser->hasPermission('access cross-group posting');
    $isCrossPostingEnabled = (bool) $socialGroupSettings->get('cross_posting.status');
    $isContentTypeAllowed = in_array($items->getEntity()->bundle(), $socialGroupSettings->get('cross_posting.content_types'), TRUE);

    return !($hasPermission && $isCrossPostingEnabled && $isContentTypeAllowed);
  }

  /**
   * Check if the array is flat or not.
   *
   * @param array $items
   *   List of items.
   *
   * @return bool
   *   TRUE if nested, FALSE if flat.
   */
  private function isNestedArray(array $items): bool {
    return is_array(reset($items));
  }

  /**
   * Check if multiple selection is available based on the options and settings.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<\Drupal\group\Entity\Group>> $items
   *   The field item list interface.
   *
   * @return bool
   *   TRUE if multiple selection is available, FALSE otherwise.
   */
  private function isMultipleSelectionAvailable(FieldItemListInterface $items): bool {
    $canSelectMultipleGroups = FALSE;
    $nested = $this->isNestedArray($this->options);

    // Case 1: Single level options (flat structure).
    if ($nested === FALSE) {
      if ($this->multiple && count($this->options) > 1) {
        $canSelectMultipleGroups = TRUE;
      }
    }

    // Case 2: Nested options (group types as categories).
    if ($nested === TRUE) {
      // Check if any option group contains multiple items.
      // If so, enable multiple selection to allow users to select multiple
      // items from that group.
      if (
        $this->multiple &&
        ((is_countable(reset($this->options)) ? count(reset($this->options)) : 0) > 1)
      ) {
        $canSelectMultipleGroups = TRUE;
      }
    }

    // Override the multiple selection based on the cross-posting settings.
    if ($this->disableMultipleSelection($items)) {
      $canSelectMultipleGroups = FALSE;
    }

    $this->multiple = $canSelectMultipleGroups;
    return $this->multiple;
  }

}
