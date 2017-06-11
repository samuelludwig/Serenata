<?php

namespace PhpIntegrator\Analysis;

use RuntimeException;

/**
 * Interface for classes that retrieve a  list of namespaces for a file.
 */
interface FileNamespaceListProviderInterface
{
     /**
      * @param string $file
      *
      * @throws RuntimeException
      *
      * @return array[]
      */
     public function getAllForFile(string $file): array;
}
