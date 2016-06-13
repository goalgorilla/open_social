<?php

namespace Drupal\activity_logger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;
use Drupal\activity_logger\Service\ContextGetter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class ActivityLoggerController extends ControllerBase {

  private $contextGetter;

  public function __construct(ContextGetter $contextGetter) {
    $this->contextGetter = $contextGetter;
  }


  public static function create(ContainerInterface $container) {
    // Get context getter from container.
    $contextGetter = $container->get('activity_logger.context_getter');

    return new static($contextGetter);
  }

  public function getContext(Entity $entity) {
    // Get the context from the entity.
    $contextgetter = $this->contextGetter->getContext($entity);

    return new Response($contextgetter);
  }

}
