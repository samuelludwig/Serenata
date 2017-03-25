<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryInterface;

/**
 * Checks if a specified (normal parameter) type is semantically equal to a docblock type specification.
 */
class ParameterDocblockTypeSemanticEqualityChecker
{
    /**
     * @var FileTypeResolverFactoryInterface
     */
    private $fileTypeResolverFactory;

    /**
     * @var TypeAnalyzer
     */
    private $typeAnalyzer;

    /**
     * @param FileTypeResolverFactoryInterface $fileTypeResolverFactory
     * @param TypeAnalyzer                     $typeAnalyzer
     */
    public function __construct(FileTypeResolverFactoryInterface $fileTypeResolverFactory, TypeAnalyzer $typeAnalyzer)
    {
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
        $this->typeAnalyzer = $typeAnalyzer;
    }

    /**
     * @param array  $parameter
     * @param array  $docblockParameter
     * @param string $filePath
     * @param int    $line
     *
     * @return bool
     */
    public function isEqual(array $parameter, array $docblockParameter, string $filePath, int $line): bool
    {
        $fileTypeResolver = $this->fileTypeResolverFactory->create($filePath);

        // FIXME: This resolving won't work properly for array docblock types (e.g. "Foo[]") nor for special cases
        // such as compound types (e.g. "A|B").
        $parameterType = $parameter['type'];
        $parameterType = $fileTypeResolver->resolve($parameterType, $line);

        if ($parameter['isVariadic']) {
            $parameterType .= '[]';
        }

        $docblockType = $docblockParameter['type'];
        $docblockType = $fileTypeResolver->resolve($docblockType, $line);

        $isTypeConformant = $this->isTypeConformantWithDocblockType($parameterType, $docblockType);

        if ($isTypeConformant && $parameter['isReference'] === $docblockParameter['isReference']) {
            return true;
        }

        return false;
    }

    /**
     * Returns a boolean indicating if the specified type (i.e. from a type hint) is valid according to the passed
     * docblock type identifier.
     *
     * @param string $type
     * @param string $typeSpecification
     *
     * @return bool
     */
    protected function isTypeConformantWithDocblockType(string $type, string $typeSpecification): bool
    {
        $docblockTypes = $this->typeAnalyzer->getTypesForTypeSpecification($typeSpecification);

        return $this->isTypeConformantWithDocblockTypes($type, $docblockTypes);
    }

    /**
     * @param string   $type
     * @param string[] $docblockTypes
     *
     * @return bool
     */
    protected function isTypeConformantWithDocblockTypes(string $type, array $docblockTypes): bool
    {
        $isPresent = in_array($type, $docblockTypes);

        if (!$isPresent && $type === 'array') {
            foreach ($docblockTypes as $docblockType) {
                // The 'type[]' syntax is also valid for the 'array' type hint.
                if ($this->typeAnalyzer->isArraySyntaxTypeHint($docblockType)) {
                    return true;
                }
            }
        }

        return $isPresent;
    }
}
