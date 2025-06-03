<?php

namespace Drupal\social_group\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\select2\Plugin\Field\FieldWidget\Select2EntityReferenceWidget;
use Drupal\user\EntityOwnerInterface;
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
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $currentUser;
    $this->pluginManager = $pluginManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->userManager = $entity_type_manager->getStorage('user');
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
      $container->get('entity_type.manager')
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

    // If the user can administer content and groups, we allow them to
    // override this. Otherwise, we stick to the original owner.
    if ($entity instanceof EntityOwnerInterface && !$groupsAdmin) {
      if ($type === 'node') {
        $permission = 'administer nodes';
      }
      else {
        $definition = $this->entityTypeManager->getDefinition($type);

        if ($definition !== NULL) {
          $permission = $definition->getAdminPermission();
        }
      }

      if (
        empty($permission) ||
        !is_string($permission) ||
        !$account->hasPermission($permission)
      ) {
        $account = $entity->getOwner();
      }
    }

    // Limit the settable options for the current user account.
    $optionsProvider = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getOptionsProvider($this->column, $entity);

    if ($optionsProvider !== NULL) {
      $options = $optionsProvider->getSettableOptions($account);

      $storage = $this->entityTypeManager->getStorage('group');

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

    // We don't allow to LU to edit field if there are multiple values.
    if (count($element['#default_value']) > 1) {
      if ($socialGroupSettings->get('cross_posting.status') && !$this->currentUser->hasPermission('access cross-group posting')) {
        $element['#disabled'] = TRUE;
        $element['#description'] = $this->t('You are not allowed to edit this field!');
      }
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
      $ajaxResponse->addCommand(new InvokeCommand('#edit-field-content-visibility-' . $defaultVisibility, 'prop', ['checked', 'checked']));
    }

    foreach ($allowedVisibilityOptions as $visibility => $allowed) {
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

    $options = [];

    foreach ($groups as $group) {
      $items = social_group_get_allowed_visibility_options_per_group_type(
        (string) $group->getGroupType()->id(),
        NULL,
        $entity,
        $group,
      );

      foreach ($items as $key => $value) {
        // We always rewrite options if it is "FALSE".
        if (!isset($options[$key]) || !$value) {
          $options[$key] = $value;
        }
      }
    }

    return $options;
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
   * Check if multiple selection is available based on the options and settings.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<\Drupal\group\Entity\Group>> $items
   *   The field item list interface.
   *
   * @return bool
   *   TRUE if multiple selection is available, FALSE otherwise.
   */
  private function isMultipleSelectionAvailable(FieldItemListInterface $items): bool {
    // The 'options' array structure is either:
    // - Single level for flexible groups only (flat structure)
    // - Two levels for mixed group types
    // (nested structure with group types as categories).
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($this->options),
      \RecursiveIteratorIterator::SELF_FIRST
    );
    $optionsDepth = $iterator->getDepth() + 1;

    // Case 1: Single level options (flat structure).
    if ($optionsDepth === 1) {
      if ($this->multiple && count($this->options) > 1) {
        return TRUE;
      }
    }

    // Case 2: Nested options (group types as categories).
    if ($optionsDepth > 1) {
      // Check if any option group contains multiple items.
      // If so, enable multiple selection to allow users to select multiple
      // items from that group.
      if (
        $this->multiple &&
        ((is_countable(reset($this->options)) ? count(reset($this->options)) : 0) > 1)
      ) {
        return TRUE;
      }
    }

    // Override the multiple selection based on the cross-posting settings.
    if ($this->disableMultipleSelection($items)) {
      return FALSE;
    }

    return $this->multiple;
  }

}
