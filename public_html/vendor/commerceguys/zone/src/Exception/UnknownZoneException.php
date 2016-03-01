<?php

namespace CommerceGuys\Zone\Exception;

/**
 * Thrown when an unknown zone id is passed to the ZoneRepository.
 */
class UnknownZoneException extends \InvalidArgumentException implements ExceptionInterface
{
}
