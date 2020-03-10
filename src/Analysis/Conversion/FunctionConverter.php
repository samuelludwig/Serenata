<?php

namespace Serenata\Analysis\Conversion;

use Serenata\Indexing\Structures;

/**
 * Converts raw function data from the index to more useful data.
 *
 * @final
 */
/*final */class FunctionConverter extends AbstractConverter
{
    /**
     * @param Structures\FunctionLike $function
     *
     * @return array<string,mixed>
     */
    public function convert(Structures\FunctionLike $function): array
    {
        $parameters = [];

        foreach ($function->getParameters() as $parameter) {
            $parameters[] = [
                'name'         => $parameter->getName(),
                'typeHint'     => $parameter->getTypeHint(),
                'types'        => $this->convertDocblockType($parameter->getType()),
                'description'  => $parameter->getDescription(),
                'defaultValue' => $parameter->getDefaultValue(),
                'isReference'  => $parameter->getIsReference(),
                'isVariadic'   => $parameter->getIsVariadic(),
                'isOptional'   => $parameter->getIsOptional(),
            ];
        }

        $throwsAssoc = [];

        foreach ($function->getThrows() as $throws) {
            $throwsAssoc[] = [
                'type'        => $throws->getFqcn(),
                'description' => $throws->getDescription(),
            ];
        }

        $data = [
            'name'              => $function->getName(),
            'range'             => $function->getRange(),
            'uri'               => $function->getFile()->getUri(),

            'parameters'        => $parameters,
            'throws'            => $throwsAssoc,
            'isDeprecated'      => $function->getIsDeprecated(),
            'hasDocblock'       => $function->getHasDocblock(),
            'hasDocumentation'  => $function->getHasDocblock(),

            'shortDescription'  => $function->getShortDescription(),
            'longDescription'   => $function->getLongDescription(),
            'returnDescription' => $function->getReturnDescription(),

            'returnTypeHint'    => $function->getReturnTypeHint(),
            'returnTypes'       => $this->convertDocblockType($function->getReturnType()),
        ];

        if ($function instanceof Structures\Function_) {
            $data['fqcn'] = $function->getFqcn();
        }

        return $data;
    }
}
