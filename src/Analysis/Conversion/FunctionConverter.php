<?php

namespace PhpIntegrator\Analysis\Conversion;

use PhpIntegrator\Indexing\Structures;

/**
 * Converts raw function data from the index to more useful data.
 */
class FunctionConverter extends AbstractConverter
{
    /**
     * @param Structures\Function_ $function
     *
     * @return array
     */
    public function convert(Structures\Function_ $function): array
    {
        $parameters = [];

        foreach ($function->getParameters() as $parameter) {
            $parameters[] = [
                'name'         => $parameter->getName(),
                'typeHint'     => $parameter->getTypeHint(),
                'types'        => $this->convertTypes($parameter->getTypes()),
                'description'  => $parameter->getDescription(),
                'defaultValue' => $parameter->getDefaultValue(),
                'isNullable'   => $parameter->getIsNullable(),
                'isReference'  => $parameter->getIsReference(),
                'isVariadic'   => $parameter->getIsVariadic(),
                'isOptional'   => $parameter->getIsOptional()
            ];
        }

        $throwsAssoc = [];

        foreach ($function->getThrows() as $throws) {
            $throwsAssoc[] = [
                'type'        => $throws['type'],
                'description' => $throws['description']
            ];
        }

        return [
            'name'              => $function->getName(),
            'fqcn'              => $function->getFqcn(),
            'startLine'         => $function->getStartLine(),
            'endLine'           => $function->getEndLine(),
            'filename'          => $function->getFile()->getPath(),

            'parameters'        => $parameters,
            'throws'            => $throwsAssoc,
            'isDeprecated'      => $function->getIsDeprecated(),
            'hasDocblock'       => $function->getHasDocblock(),
            'hasDocumentation'  => $function->getHasDocblock(),

            'shortDescription'  => $function->getShortDescription(),
            'longDescription'   => $function->getLongDescription(),
            'returnDescription' => $function->getReturnDescription(),

            'returnTypeHint'    => $function->getReturnTypeHint(),
            'returnTypes'       => $this->convertTypes($function->getReturnTypes())
        ];
    }
}
