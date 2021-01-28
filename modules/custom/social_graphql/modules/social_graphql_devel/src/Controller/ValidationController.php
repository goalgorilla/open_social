<?php

namespace Drupal\social_graphql_devel\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql\Entity\ServerInterface;
use Drupal\social_graphql_devel\GraphQL\ValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the GraphiQL resolver validation.
 */
class ValidationController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The schema plugin manager.
   *
   * @var \Drupal\social_graphql_devel\GraphQL\ValidatorInterface
   */
  protected ValidatorInterface $validator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('graphql.validator'),
    );
  }

  /**
   * ValidateResolverController constructor.
   *
   * @param \Drupal\social_graphql_devel\GraphQL\ValidatorInterface $validator
   *   The GraphQL validator.
   */
  public function __construct(ValidatorInterface $validator) {
    $this->validator = $validator;
  }

  /**
   * Controller for the GraphiQL query builder IDE.
   *
   * @param \Drupal\graphql\Entity\ServerInterface $graphql_server
   *   The GraphQL server entity.
   *
   * @return array
   *   The render array.
   */
  public function report(ServerInterface $graphql_server) {
    $build = [
      'validation' => [
        '#type' => 'table',
        '#caption' => $this->t("Validation errors"),
        '#header' => [$this->t('Type'), $this->t('Field'), $this->t('Message')],
        '#empty' => $this->t("No validation errors."),
      ],
      'orphaned' => [
        '#type' => 'table',
        '#caption' => $this->t("Resolvers without schema"),
        '#header' => [$this->t('Type'), $this->t('Fields')],
        '#empty' => $this->t("No orphaned resolvers."),
      ],
      'missing' => [
        '#type' => 'table',
        '#caption' => $this->t("Fields without resolvers"),
        '#header' => [$this->t('Type'), $this->t('Fields')],
        '#empty' => $this->t("No missing resolvers."),
      ],
    ];

    foreach ($this->validator->validateSchema($graphql_server) as $error) {
      $type = '';
      if (isset($error->nodes[1]) && property_exists($error->nodes[1], 'name')) {
        $type = $error->nodes[1]->name->value;
      }
      $field = '';
      if (isset($error->nodes[0]) && property_exists($error->nodes[1], 'name')) {
        $field = $error->nodes[0]->name->value;
      }

      $build['validation'][] = [
        'type' => ['#plain_text' => $type],
        'field' => ['#plain_text' => $field],
        'message' => ['#plain_text' => $error->getMessage()],
      ];
    }

    $metrics = [
      'orphaned' => $this->validator->getOrphanedResolvers($graphql_server),
      // Ignore PageInfo since it's implemented as typed data.
      // Ignore Subscription fields since they're handled by an external
      // program.
      'missing' => $this->validator->getMissingResolvers($graphql_server, ['PageInfo', 'Subscription']),
    ];

    foreach ($metrics as $metric_type => $data) {
      foreach ($data as $type => $fields) {
        $build[$metric_type][$type] = [
          'type' => ['#plain_text' => $type],
          'fields' => [
            '#theme' => 'item_list',
            '#items' => $fields,
          ],
        ];
      }
    }

    return $build;
  }

}
