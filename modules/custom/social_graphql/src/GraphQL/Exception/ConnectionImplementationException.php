<?php

namespace Drupal\social_graphql\GraphQL\Exception;

/**
 * Exception for DataProducer.
 *
 * It is thrown when it uses a connection & does not handle metadata correctly.
 */
class ConnectionImplementationException extends \Exception {}
