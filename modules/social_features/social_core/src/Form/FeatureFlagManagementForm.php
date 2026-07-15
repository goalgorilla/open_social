<?php

declare(strict_types=1);

namespace Drupal\social_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_core\Service\FeatureFlagCheckerInterface;
use Drupal\social_core\Service\FeatureFlagManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to manage feature flags defined by modules.
 */
final class FeatureFlagManagementForm extends FormBase {

  /**
   * Constructs a FeatureFlagManagementForm object.
   */
  public function __construct(
    private readonly FeatureFlagManagerInterface $featureFlagManager,
    private readonly FeatureFlagCheckerInterface $featureFlagChecker,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(FeatureFlagManagerInterface::class),
      $container->get(FeatureFlagCheckerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_core_feature_flag_management_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $errors = $this->featureFlagManager->getValidationErrors();
    if ($errors !== []) {
      $items = [];
      foreach ($errors as $error) {
        $items[] = $this->t('@module / @flag: @message', [
          '@module' => $error['module'],
          '@flag' => $error['machine_name'],
          '@message' => $error['message'],
        ]);
      }

      $form['validation_errors'] = [
        '#type' => 'item',
        '#title' => $this->t('Feature flag validation errors'),
        '#prefix' => '<div class="messages messages--error">',
        '#suffix' => '</div>',
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }

    $definitions = $this->featureFlagManager->getDefinitions();

    $form['flags'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Enabled'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Date introduced'),
        $this->t('Module'),
      ],
      '#empty' => $this->t('No feature flags are defined.'),
    ];

    foreach ($definitions as $machine_name => $definition) {
      $form['flags'][$machine_name] = [
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @label', [
            '@label' => $definition->label,
          ]),
          '#title_display' => 'invisible',
          '#default_value' => $this->featureFlagChecker->isEnabled($machine_name),
        ],
        'label' => [
          '#plain_text' => $definition->label,
        ],
        'description' => [
          '#plain_text' => $definition->description,
        ],
        'date_introduced' => [
          '#plain_text' => $definition->dateIntroduced,
        ],
        'provider' => [
          '#plain_text' => $definition->provider,
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('flags', []);
    if (!is_array($values)) {
      return;
    }

    foreach (array_keys($this->featureFlagManager->getDefinitions()) as $machine_name) {
      $enabled = !empty($values[$machine_name]['enabled']);
      $this->featureFlagManager->setEnabled($machine_name, $enabled);
    }

    $this->messenger()->addStatus($this->t('Feature flag settings have been saved.'));
  }

}
