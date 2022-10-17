<?php

namespace Drupal\mentions\Plugin\Filter;

use Drupal\Core\Config\Config;
use Drupal\Core\Session\AccountInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\mentions\MentionsPluginInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\mentions\MentionsPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;

/**
 * Class FilterMentions.
 *
 * @package Drupal\mentions\Plugin\Filter
 *
 * @Filter(
 *   id = "filter_mentions",
 *   title = @Translation("Mentions Filter"),
 *   description = @Translation("Configure via the <a href='/admin/structure/mentions'>Mention types</a> page."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   settings = {
 *     "mentions_filter" = {}
 *   },
 *   weight = -10
 * )
 */
class MentionsFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $config;

  /**
   * The mentions plugin manager.
   *
   * @var \Drupal\mentions\MentionsPluginManager
   */
  protected MentionsPluginManager $mentionsManager;

  /**
   * The available mention types.
   *
   * @var string[]
   */
  private array $mentionTypes = [];

  /**
   * The input settings per config.
   *
   * @var array
   */
  private array $inputSettings = [];

  /**
   * The output settings per config.
   *
   * @var array
   */
  private array $outputSettings = [];

  /**
   * The text format id used for mentions.
   *
   * @var string|null
   */
  private ?string $textFormat;

  /**
   * MentionsFilter constructor.
   *
   * @param array $configuration
   *   Config array.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $render
   *   The renderer.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   * @param \Drupal\mentions\MentionsPluginManager $mentions_manager
   *   The mentions manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $render, ConfigFactory $config, MentionsPluginManager $mentions_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mentionsManager = $mentions_manager;
    $this->renderer = $render;
    $this->config = $config;

    if (!isset($plugin_definition['provider'])) {
      $plugin_definition['provider'] = 'mentions';
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_manager = $container->get('entity_type.manager');
    $renderer = $container->get('renderer');
    $config = $container->get('config.factory');
    $mentions_manager = $container->get('plugin.manager.mentions');

    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $renderer,
      $config,
      $mentions_manager
    );
  }

  /**
   * Returns the settings.
   *
   * @return array
   *   A list of settings.
   */
  public function getSettings(): array {
    return $this->settings;
  }

  /**
   * Checks if there are mentionTypes.
   *
   * @return bool
   *   TRUE if there are mentionTypes, otherwise FALSE.
   */
  public function checkMentionTypes(): bool {
    $settings = $this->settings;

    if (isset($settings['mentions_filter'])) {
      $configs = $this->config->listAll('mentions.mentions_type');

      foreach ($configs as $config) {
        $this->mentionTypes[] = str_replace('mentions.mentions_type.', '', $config);
      }
    }

    return !empty($this->mentionTypes);
  }

  /**
   * Checks if a textFormat filter should be applied.
   *
   * @return bool
   *   TRUE if filter should applied, otherwise FALSE.
   */
  public function shouldApplyFilter(): bool {
    if ($this->checkMentionTypes()) {
      return TRUE;
    }
    elseif ($this->textFormat && ($format = FilterFormat::load($this->textFormat))) {
      $filters = $format->get('filters');

      if (!empty($filters['filter_mentions']['status'])) {
        $this->settings = $filters['filter_mentions']['settings'];

        return $this->checkMentionTypes();
      }
    }

    return FALSE;
  }

  /**
   * Gets the mentions in text.
   *
   * @param string $text
   *   The text to find mentions in.
   *
   * @return array
   *   A list of mentions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getMentions(string $text): array {
    $mentions = [];

    foreach ($this->getConfigsByMentionTypes() as $config_name => $config) {
      $input_settings = $this->getInputSettingsByConfig($config);
      $this->setInputSettings($config_name, $config);

      if (!$this->checkInputSettingsByConfigName($config_name)) {
        continue;
      }

      $this->setOutputSettings($config_name, $config);
      $mention_type = $config->get('mention_type');
      $mention = $this->mentionsManager->createInstance($mention_type);

      if ($mention instanceof MentionsPluginInterface) {
        $pattern = $this->getPatternByInputSettings($input_settings);

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
          $target = $mention->targetCallback($match[1], $input_settings);

          if (!empty($target)) {
            $mentions[$match[0]] = [
              'type' => $mention_type,
              'source' => [
                'string' => $match[0],
                'match' => $match[1],
              ],
              'target' => $target,
              'config_name' => $config_name,
            ];
          }
        }
      }
    }

    return $mentions;
  }

  /**
   * Filters mentions in a text.
   *
   * @param string $text
   *   The text containing the possible mentions.
   *
   * @return string
   *   The processed text.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function filterMentions(string $text): string {
    $mentions = $this->getMentions($text);

    foreach ($mentions as $match) {
      if ($this->mentionsManager->hasDefinition($match['type'])) {
        $mention = $this->mentionsManager->createInstance($match['type']);

        if ($mention instanceof MentionsPluginInterface) {
          $output_settings = $this->outputSettings[$match['config_name']];
          $output = $mention->outputCallback($match, $output_settings);
          $build = [
            '#theme' => 'mention_link',
            '#mention_id' => $match['target']['entity_id'],
            '#link' => base_path() . $output['link'],
            '#render_link' => $output_settings['renderlink'],
            '#render_value' => $output['value'],
            '#render_plain' => $output['render_plain'] ?? FALSE,
          ];
          $mentions = $this->renderer->render($build);
          $text = str_replace($match['source']['string'], $mentions, $text);
        }
      }
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if ($this->shouldApplyFilter()) {
      $text = $this->filterMentions($text);

      return new FilterProcessResult($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    /** @var string[] $configs */
    $configs = $this->config->listAll('mentions.mentions_type');
    $candidate_entity_types = [];

    foreach ($configs as $config) {
      $mentions_name = str_replace('mentions.mentions_type.', '', $config);
      $candidate_entity_types[$mentions_name] = $mentions_name;
    }

    if (count($candidate_entity_types) == 0) {
      return parent::settingsForm($form, $form_state);
    }

    $form['mentions_filter'] = [
      '#type' => 'checkboxes',
      '#options' => $candidate_entity_types,
      '#default_value' => $this->settings['mentions_filter'],
      '#title' => $this->t('Mentions types'),
    ];

    return $form;
  }

  /**
   * Set the text format.
   *
   * @param string|null $text_format
   *   The text format to set.
   */
  public function setTextFormat(?string $text_format): void {
    $this->textFormat = $text_format;
  }

  /**
   * Retrieves the data necessary to initialize the mentions.
   *
   * @param string $text
   *   The text to find mentions in.
   *
   * @return array
   *   Return mentions conform mentions lib.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getInitMentionsData(string $text): array {
    $mentions = [];

    foreach ($this->getConfigsByMentionTypes() as $config_name => $config) {
      $input_settings = $this->getInputSettingsByConfig($config);
      $this->setInputSettings($config_name, $config);

      if (!$this->checkInputSettingsByConfigName($config_name)) {
        continue;
      }

      $pattern = $this->getPatternByInputSettings($input_settings);

      if ($text && preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          if (!$text) {
            continue;
          }
          // To determine properly the string position,
          // we need to convert special chars to normal chars.
          $clean_str = preg_replace('/&([a-z])[a-z]+;/i', "$1", htmlentities($text));
          if (!$clean_str) {
            continue;
          }
          $position = strpos($clean_str, $match[0]);
          $entity = $this->entityTypeManager->getStorage($input_settings['entity_type'])->load($match[1]);

          if ($entity instanceof ProfileInterface || $entity instanceof AccountInterface) {
            $account = $entity instanceof ProfileInterface ? $entity->getOwner() : $entity;
            $name = strip_tags($account->getDisplayName());
            $mentions[] = [
              'name' => $name,
              'pos' => $position,
              $input_settings['value'] => $match[1],
            ];
            $text = preg_replace($pattern, $name, $text, 1);
          }
        }
      }
    }

    return [
      'mentions' => $mentions,
      'text' => $text,
    ];
  }

  /**
   * Get config objects by mention types.
   *
   * @return array
   *   Returns config objects in an array.
   */
  private function getConfigsByMentionTypes(): array {
    $configs = [];
    foreach ($this->mentionTypes as $config_name) {
      $configs[$config_name] = $this->config->get("mentions.mentions_type.{$config_name}");
    }

    return $configs;
  }

  /**
   * Set the input settings for the filter.
   *
   * @param string $config_name
   *   The config name.
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   */
  private function setInputSettings(string $config_name, Config $config): void {
    $this->inputSettings[$config_name] = $this->getInputSettingsByConfig($config);
  }

  /**
   * Set the output settings for the filter.
   *
   * @param string $config_name
   *   The config name.
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   */
  private function setOutputSettings(string $config_name, Config $config): void {
    $this->outputSettings[$config_name] = $this->getOutputSettingsByConfig($config);
  }

  /**
   * Validates the associated input settings.
   *
   * @param string $config_name
   *   The config name.
   *
   * @return bool
   *   Return boolean.
   */
  private function checkInputSettingsByConfigName(string $config_name): bool {
    if (!isset($this->inputSettings[$config_name]['entity_type']) || empty($this->settings['mentions_filter'][$config_name])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get input settings by configuration.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The mentions config object.
   *
   * @return array
   *   Returns the input settings.
   */
  private function getInputSettingsByConfig(Config $config): array {
    return [
      'prefix' => $config->get('input.prefix'),
      'suffix' => $config->get('input.suffix'),
      'entity_type' => $config->get('input.entity_type'),
      'value' => $config->get('input.inputvalue'),
    ];
  }

  /**
   * Get output setting by configuration.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The mentions config object.
   *
   * @return array
   *   Returns the ouput settings.
   */
  private function getOutputSettingsByConfig(Config $config): array {
    return [
      'value' => $config->get('output.outputvalue'),
      'renderlink' => (bool) $config->get('output.renderlink'),
      'rendertextbox' => $config->get('output.renderlinktextbox'),
    ];
  }

  /**
   * Get regex pattern by input settings.
   *
   * @param array $input_settings
   *   The input settings.
   *
   * @return string
   *   Returns the regex pattern.
   */
  private function getPatternByInputSettings(array $input_settings): string {
    return '/(?:' . preg_quote($input_settings['prefix']) . ')([ a-z0-9@+_.\'-]+)' . preg_quote($input_settings['suffix']) . '/';
  }

}
