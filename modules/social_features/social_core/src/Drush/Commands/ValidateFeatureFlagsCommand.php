<?php

declare(strict_types=1);

namespace Drupal\social_core\Drush\Commands;

use Drupal\social_core\Service\FeatureFlagManagerInterface;
use Drush\Commands\AutowireTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validates social feature flag definitions.
 */
#[AsCommand(
  name: self::NAME,
  description: 'Validates feature flag definitions across all modules.',
)]
final class ValidateFeatureFlagsCommand extends Command {

  use AutowireTrait;

  /**
   * The command name.
   */
  public const NAME = 'social:validate-feature-flags';

  /**
   * Constructs a ValidateFeatureFlagsCommand object.
   */
  public function __construct(
    private readonly FeatureFlagManagerInterface $featureFlagManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $errors = $this->featureFlagManager->getValidationErrors();

    if ($errors !== []) {
      $table = new Table($output);
      $table->setHeaders(['Module', 'Feature flag', 'Message']);
      foreach ($errors as $error) {
        $table->addRow([
          $error['module'],
          $error['machine_name'],
          $error['message'],
        ]);
      }
      $table->render();

      return Command::FAILURE;
    }

    $count = count($this->featureFlagManager->getDefinitions());
    $output->writeln(sprintf('<info>Validated %d feature flag(s).</info>', $count));

    return Command::SUCCESS;
  }

}
