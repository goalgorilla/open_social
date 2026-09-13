<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Action;

use Drupal\data_policy\Entity\InformBlock;
use Drupal\social_gdpr\TestControlInterface\Success\InformBlocksCreatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoints for inform block creation.
 *
 * Replaces GDPRContext::createInformBlock().
 */
final class CreateInformBlocks {

  /**
   * Creates multiple inform blocks from a table; one row per block.
   *
   * @param list<\Drupal\social_gdpr\TestControlInterface\Dto\CreateInformBlockRequest> $body
   *   The list of inform blocks to create.
   *
   * @return \Drupal\social_gdpr\TestControlInterface\Success\InformBlocksCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'inform_block_create_bulk',
    patterns: ['inform_blocks:'],
    type: OperationType::Write,
  )]
  public function create(
    #[Example([
      [
        'id' => 1,
        'label' => 'Inform block title',
        'page' => '/path/example',
        'summary' => 'Inform block summary',
        'body' => 'Inform block description',
        'status' => TRUE,
      ],
    ])]
    array $body,
  ): InformBlocksCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No inform block rows in request body; refusing to return success with zero inform blocks created.');
    }

    $ids = [];
    foreach ($body as $informBlock) {
      $informBlockObject = InformBlock::create($informBlock->toValuesArray());
      $informBlockObject->save();
      $ids[] = (int) $informBlockObject->id();
    }

    return new InformBlocksCreatedSuccess(ids: $ids);
  }

}
