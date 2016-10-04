<?php

namespace PhpIntegrator\Analysis\Relations;

use ArrayObject;

/**
 * Deals with resolving implementation of interfaces for classlikes.
 *
 * "Implementation" in this context means nothing more than "is using an interface after the implements keyword". In
 * other words, it doesn't matter if the class is actually implementing the methods from the interface, as long as it's
 * (directly) referencing it, this class handles it.
 */
class InterfaceImplementationResolver extends AbstractResolver
{
    /**
     * @param ArrayObject $interface
     * @param ArrayObject $class
     *
     * @return ArrayObject
     */
    public function resolveImplementationOf(ArrayObject $interface, ArrayObject $class)
    {
        foreach ($interface['constants'] as $constant) {
            $this->resolveInheritanceOfConstant($constant, $class);
        }

        foreach ($interface['methods'] as $method) {
            $this->resolveImplementationOfMethod($method, $class);
        }
    }

    /**
     * @param array       $parentConstantData
     * @param ArrayObject $class
     */
    protected function resolveInheritanceOfConstant(array $parentConstantData, ArrayObject $class)
    {
        $class['constants'][$parentConstantData['name']] = $parentConstantData + [
            'declaringClass' => [
                'name'      => $class['name'],
                'filename'  => $class['filename'],
                'startLine' => $class['startLine'],
                'endLine'   => $class['endLine'],
                'type'      => $class['type']
            ],

            'declaringStructure' => [
                'name'            => $class['name'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type'],
                'startLineMember' => $parentConstantData['startLine'],
                'endLineMember'   => $parentConstantData['endLine']
            ]
        ];
    }

    /**
     * @param array       $parentMethodData
     * @param ArrayObject $class
     */
    protected function resolveImplementationOfMethod(array $parentMethodData, ArrayObject $class)
    {
        $inheritedData = [];
        $childMethod = null;
        $overrideData = null;
        $implementationData = null;

        if (isset($class['methods'][$parentMethodData['name']])) {
            $childMethod = $class['methods'][$parentMethodData['name']];

            if ($parentMethodData['declaringStructure']['type'] === 'interface') {
                $implementationData = [
                    'declaringClass'     => $parentMethodData['declaringClass'],
                    'declaringStructure' => $parentMethodData['declaringStructure'],
                    'startLine'          => $parentMethodData['startLine'],
                    'endLine'            => $parentMethodData['endLine']
                ];
            } else {
                $overrideData = [
                    'declaringClass'     => $parentMethodData['declaringClass'],
                    'declaringStructure' => $parentMethodData['declaringStructure'],
                    'startLine'          => $parentMethodData['startLine'],
                    'endLine'            => $parentMethodData['endLine'],
                    'wasAbstract'        => $parentMethodData['isAbstract']
                ];
            }

            if ($parentMethodData['hasDocumentation'] && $this->isInheritingFullDocumentation($childMethod)) {
                $inheritedData = $this->extractInheritedMethodInfo($parentMethodData, $childMethod);
            } else {
                $inheritedData['longDescription'] = $this->resolveInheritDoc(
                    $childMethod['longDescription'],
                    $parentMethodData['longDescription']
                );
            }

            $childMethod['declaringClass'] = [
                'name'            => $class['name'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type']
            ];

            $childMethod['declaringStructure'] = [
                'name'            => $class['name'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type'],
                'startLineMember' => $childMethod['startLine'],
                'endLineMember'   => $childMethod['endLine']
            ];
        } else {
            $childMethod = [];
        }

        $class['methods'][$parentMethodData['name']] = array_merge($parentMethodData, $childMethod, $inheritedData, [
            'override'       => $overrideData,
            'implementation' => $implementationData,

            'declaringClass' => [
                'name'            => $class['name'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type']
            ]
        ]);
    }
}
