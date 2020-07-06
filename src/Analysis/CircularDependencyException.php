<?php

namespace Serenata\Analysis;

/**
 * Indicates a circular dependency between classlikes (i.e. it extends or implements itself).
 */
final class CircularDependencyException extends ClasslikeBuildingFailedException
{
}
