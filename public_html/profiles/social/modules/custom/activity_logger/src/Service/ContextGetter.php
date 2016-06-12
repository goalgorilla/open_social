<?php

namespace Drupal\activity_logger\Service;

use Drupal\Core\Entity\Entity;
use Symfony\Component\HttpFoundation\Response;

class ContextGetter {

  public function getContext(Entity $entity) {
    return new Response("group");
  }

  public function doSomeMore() {
    return new Response("MEER MEER!!");
  }
}
/*
:message new fields
dynamic entity reference
- entity_id
- entity_type

:messages types
- remove _context from name
- fill context field based on context the item is post in
-
*/
