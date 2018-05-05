<?php

namespace Serenata\Analysis;

use RuntimeException;

/**
 * Indicates a circular dependency between classlikes (i.e. a class extending itself or an interface implementing
 * itself).
 */
final class CircularDependencyException extends RuntimeException
{

}
