<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds an additional field containing the rendered item.
 *
 * @SearchApiProcessor(
 *   id = "rendered_item",
 *   label = @Translation("Rendered item"),
 *   description = @Translation("Adds an additional field containing the rendered item as it would look when viewed."),
 *   stages = {
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -30
 *   }
 * )
 */
class RenderedItem extends ProcessorPluginBase {

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|null
   */
  protected $currentUser;

  /**
   * The renderer to use.
   *
   * @var \Drupal\Core\Render\RendererInterface|null
   */
  protected $renderer;

  /**
   * The logger to use for log messages.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $container->get('current_user');
    $plugin->setCurrentUser($current_user);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $container->get('renderer');
    $plugin->setRenderer($renderer);

    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.factory')->get('search_api');
    $plugin->setLogger($logger);

    return $plugin;
  }

  /**
   * Retrieves the current user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::currentUser();
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * Retrieves the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function getRenderer() {
    return $this->renderer ?: \Drupal::service('renderer');
  }

  /**
   * Sets the renderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The new renderer.
   *
   * @return $this
   */
  public function setRenderer(RendererInterface $renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  /**
   * Retrieves the logger to use for log messages.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger ?: \Drupal::logger('search_api');
  }

  /**
   * Sets the logger to use for log messages.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The new logger.
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  // @todo Add a supportsIndex() implementation that checks whether there is
  //   actually any datasource present which supports viewing.

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'roles' => array(AccountInterface::ANONYMOUS_ROLE),
      'view_mode' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $roles = user_role_names();
    $form['roles'] = array(
      '#type' => 'select',
      '#title' => $this->t('User roles'),
      '#description' => $this->t('Your item will be rendered as seen by a user with the selected roles. We recommend to just use "@anonymous" here to prevent data leaking out to unauthorized roles.', array('@anonymous' => $roles[AccountInterface::ANONYMOUS_ROLE])),
      '#options' => $roles,
      '#multiple' => TRUE,
      '#default_value' => $this->configuration['roles'],
      '#required' => TRUE,
    );

    $form['view_mode'] = array(
      '#type' => 'item',
      '#description' => $this->t('You can choose the view modes to use for rendering the items of different datasources and bundles. We recommend using a dedicated view mode (e.g., the "Search index" view mode available by default for content) to make sure that only relevant data (especially no field labels) will be included in the index.'),
    );

    $options_present = FALSE;
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $bundles = $datasource->getBundles();
      foreach ($bundles as $bundle_id => $bundle_label) {
        $view_modes = $datasource->getViewModes($bundle_id);
        $view_modes[''] = $this->t("Don't include the rendered item.");
        if (count($view_modes) > 1) {
          $form['view_mode'][$datasource_id][$bundle_id] = array(
            '#type' => 'select',
            '#title' => $this->t('View mode for %datasource » %bundle', array('%datasource' => $datasource->label(), '%bundle' => $bundle_label)),
            '#options' => $view_modes,
          );
          if (isset($this->configuration['view_mode'][$datasource_id][$bundle_id])) {
            $form['view_mode'][$datasource_id][$bundle_id]['#default_value'] = $this->configuration['view_mode'][$datasource_id][$bundle_id];
          }
          $options_present = TRUE;
        }
        else {
          $form['view_mode'][$datasource_id][$bundle_id] = array(
            '#type' => 'value',
            '#value' => $view_modes ? key($view_modes) : FALSE,
          );
        }
      }
    }
    // If there are no datasources/bundles with more than one view mode, don't
    // display the description either.
    if (!$options_present) {
      unset($form['view_mode']['#type']);
      unset($form['view_mode']['#description']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    $definition = array(
      'type' => 'text',
      'label' => $this->t('Rendered HTML output'),
      'description' => $this->t('The complete HTML which would be displayed when viewing the item'),
    );
    $properties['rendered_item'] = new DataDefinition($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    $this->ensureField(NULL, 'rendered_item');
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    // Change the current user to our dummy implementation to ensure we are
    // using the configured roles.
    $original_user = $this->currentUser->getAccount();
    $this->currentUser->setAccount(new UserSession(array('roles' => $this->configuration['roles'])));

    // Count of items that don't have a view mode.
    $unset_view_modes = 0;

    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      foreach ($this->filterForPropertyPath($item->getFields(), 'rendered_item') as $field) {
        $datasource_id = $item->getDatasourceId();
        $datasource = $item->getDatasource();
        $bundle = $datasource->getItemBundle($item->getOriginalObject());
        // When no view mode has been set for the bundle, or it has been set to
        // "Don't include the rendered item", skip this item.
        if (empty($this->configuration['view_mode'][$datasource_id][$bundle])) {
          // If it was really not set, also notify the user through the log.
          if (!isset($this->configuration['view_mode'][$datasource_id][$bundle])) {
            ++$unset_view_modes;
          }
          continue;
        }
        else {
          $view_mode = (string) $this->configuration['view_mode'][$datasource_id][$bundle];
        }

        $build = $datasource->viewItem($item->getOriginalObject(), $view_mode);
        $value = (string) $this->getRenderer()->renderPlain($build);
        if ($value) {
          $field->addValue($value);
        }
      }
    }

    if ($unset_view_modes > 0) {
      $context = array(
        '%index' => $this->index->label(),
        '%processor' => $this->label(),
        '@count' => $unset_view_modes,
      );
      $this->getLogger()->warning('Warning: While indexing items on search index %index, @count item(s) did not have a view mode configured for the %processor processor.', $context);
    }

    // Restore the original user.
    $this->currentUser->setAccount($original_user);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $view_modes = $this->configuration['view_mode'];
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      if (($entity_type_id = $datasource->getEntityTypeId()) && !empty($view_modes[$datasource_id])) {
        foreach ($view_modes[$datasource_id] as $view_mode) {
          if ($view_mode) {
            /** @var \Drupal\Core\Entity\EntityViewModeInterface $view_mode_entity */
            $view_mode_entity = EntityViewMode::load($entity_type_id . '.' . $view_mode);
            if ($view_mode_entity) {
              $this->addDependency($view_mode_entity->getConfigDependencyKey(), $view_mode_entity->getConfigDependencyName());
            }
          }
        }
      }
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // All dependencies of this processor are entity view modes, so we go
    // through our configuration and remove the settings for all datasources or
    // bundles which were set to one of the removed view modes. This will always
    // result in the removal of all those dependencies.
    // The code is highly similar to calculateDependencies(), only that we
    // remove the setting (if necessary) instead of adding a dependency.
    $view_modes = $this->configuration['view_mode'];
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      if ($entity_type_id = $datasource->getEntityTypeId() && !empty($view_modes[$datasource_id])) {
        foreach ($view_modes[$datasource_id] as $bundle => $view_mode_id) {
          if ($view_mode_id) {
            /** @var \Drupal\Core\Entity\EntityViewModeInterface $view_mode */
            $view_mode = EntityViewMode::load($entity_type_id . '.' . $view_mode_id);
            if ($view_mode) {
              $dependency_key = $view_mode->getConfigDependencyKey();
              $dependency_name = $view_mode->getConfigDependencyName();
              if (!empty($dependencies[$dependency_key][$dependency_name])) {
                unset($this->configuration['view_mode'][$datasource_id][$bundle]);
              }
            }
          }
        }
      }
    }

    return TRUE;
  }

}
