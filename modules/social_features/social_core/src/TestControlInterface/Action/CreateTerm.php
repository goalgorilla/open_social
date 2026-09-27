<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_core\TestControlInterface\Dto\CreateTermRequest;
use Drupal\social_core\TestControlInterface\Success\TermsBulkCreatedSuccess;
use Drupal\taxonomy\Entity\Term;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoint for taxonomy term creation on an existing vocabulary.
 */
final class CreateTerm {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Creates one or more terms on an existing vocabulary.
   *
   * @param string $vocabulary
   *   The vocabulary machine name.
   * @param list<CreateTermRequest> $body
   *   The terms to create.
   *
   * @return \Drupal\social_core\TestControlInterface\Success\TermsBulkCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'term_create_bulk',
    patterns: [':vocabulary terms:'],
    type: OperationType::Write,
  )]
  public function createTerms(
    string $vocabulary,
    #[Example([['name' => 'Snook', 'parent' => 'Fish']])]
    array $body,
  ): TermsBulkCreatedSuccess {
    if ($this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vocabulary) === NULL) {
      throw new BadRequestHttpException(sprintf("Vocabulary '%s' does not exist.", $vocabulary));
    }
    if ($body === []) {
      throw new BadRequestHttpException('No term rows in request body; refusing to return success with zero terms created.');
    }

    $tids = [];
    foreach ($body as $row) {
      $tids[] = $this->createSingleTerm($vocabulary, $row);
    }

    return new TermsBulkCreatedSuccess(tids: $tids);
  }

  /**
   * Creates a single taxonomy term and returns its term ID.
   */
  private function createSingleTerm(string $vocabulary, CreateTermRequest $row): int {
    $values = [
      'vid' => $vocabulary,
      'name' => $row->name,
    ];

    if ($row->tid !== NULL) {
      if ($this->entityTypeManager->getStorage('taxonomy_term')->load($row->tid) !== NULL) {
        throw new BadRequestHttpException(sprintf("A term with ID '%d' already exists.", $row->tid));
      }
      $values['tid'] = $row->tid;
    }
    if ($row->description !== NULL) {
      $values['description'] = $row->description;
    }
    if ($row->weight !== NULL) {
      $values['weight'] = $row->weight;
    }
    if ($row->parent !== NULL) {
      $values['parent'] = $this->resolveParentTermId($vocabulary, $row->parent);
    }

    $term = Term::create($values);
    $term->save();

    return (int) $term->id();
  }

  /**
   * Resolves a parent term's ID by name within a vocabulary.
   */
  private function resolveParentTermId(string $vocabulary, string $parentName): int {
    $ids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', $vocabulary)
      ->condition('name', $parentName)
      ->execute();
    if ($ids === []) {
      throw new BadRequestHttpException(sprintf("Parent term '%s' does not exist in vocabulary '%s'.", $parentName, $vocabulary));
    }

    return (int) reset($ids);
  }

}
