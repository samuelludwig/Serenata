<?php

namespace Serenata\Analysis\Relations;

use ArrayObject;

/**
 * Deals with resolving inheritance for classlikes.
 */
final class InheritanceResolver extends AbstractResolver
{
    /**
     * @param ArrayObject $parent
     * @param ArrayObject $class
     *
     * @return void
     */
    public function resolveInheritanceOf(ArrayObject $parent, ArrayObject $class): void
    {
        if ($class['shortDescription'] === '' || $class['shortDescription'] === null) {
            $class['shortDescription'] = $parent['shortDescription'];
        }

        if ($class['longDescription'] === '' || $class['longDescription'] === null) {
            $class['longDescription'] = $parent['longDescription'];
        } elseif ($parent['longDescription'] !== null) {
            $class['longDescription'] = $this->resolveInheritDoc($class['longDescription'], $parent['longDescription']);
        }

        $class['hasDocumentation'] = $class['hasDocumentation'] || $parent['hasDocumentation'];

        $class['traits']     = array_merge($class['traits'], $parent['traits']);
        $class['interfaces'] = array_merge($class['interfaces'], $parent['interfaces']);
        $class['parents']    = array_merge($class['parents'], $parent['parents']);

        foreach ($parent['constants'] as $constant) {
            $this->resolveInheritanceOfConstant($constant, $class);
        }

        foreach ($parent['properties'] as $property) {
            $this->resolveInheritanceOfProperty($property, $class);
        }

        foreach ($parent['methods'] as $method) {
            $this->resolveInheritanceOfMethod($method, $class);
        }
    }

    /**
     * @param array       $parentConstantData
     * @param ArrayObject $class
     *
     * @return void
     */
    private function resolveInheritanceOfConstant(array $parentConstantData, ArrayObject $class): void
    {
        $class['constants'][$parentConstantData['name']] = $parentConstantData + [
            'declaringClass' => [
                'fqcn'      => $class['fqcn'],
                'uri'       => $class['uri'],
                'range'     => $class['range'],
                'type'      => $class['type'],
            ],

            'declaringStructure' => [
                'fqcn'            => $class['fqcn'],
                'uri'             => $class['uri'],
                'range'           => $class['range'],
                'type'            => $class['type'],
                'memberRange'     => $parentConstantData['range'],
            ],
        ];
    }

    /**
     * @param array       $parentPropertyData
     * @param ArrayObject $class
     *
     * @return void
     */
    private function resolveInheritanceOfProperty(array $parentPropertyData, ArrayObject $class): void
    {
        $inheritedData = [];
        $childProperty = null;
        $overrideData = null;

        if (isset($class['properties'][$parentPropertyData['name']])) {
            $childProperty = $class['properties'][$parentPropertyData['name']];

            $overrideData = [
                'declaringClass'     => $parentPropertyData['declaringClass'],
                'declaringStructure' => $parentPropertyData['declaringStructure'],
                'range'              => $parentPropertyData['range'],
            ];

            if ($parentPropertyData['hasDocumentation'] && $this->isInheritingFullDocumentation($childProperty)) {
                $inheritedData = $this->extractInheritedPropertyInfo($parentPropertyData);
            } elseif ($childProperty['longDescription'] !== null && $parentPropertyData['longDescription'] !== null) {
                $inheritedData['longDescription'] = $this->resolveInheritDoc(
                    $childProperty['longDescription'],
                    $parentPropertyData['longDescription']
                );
            }

            $childProperty['declaringClass'] = [
                'fqcn'            => $class['fqcn'],
                'uri'             => $class['uri'],
                'range'           => $class['range'],
                'type'            => $class['type'],
            ];

            $childProperty['declaringStructure'] = [
                'fqcn'            => $class['fqcn'],
                'uri'             => $class['uri'],
                'range'           => $class['range'],
                'type'            => $class['type'],
                'memberRange'     => $childProperty['range'],
            ];
        } else {
            $childProperty = [];
        }

        $class['properties'][$parentPropertyData['name']] = array_merge(
            $parentPropertyData,
            $childProperty,
            $inheritedData,
            ['override' => $overrideData]
        );
    }

    /**
     * @param array       $parentMethodData
     * @param ArrayObject $class
     *
     * @return void
     */
    private function resolveInheritanceOfMethod(array $parentMethodData, ArrayObject $class): void
    {
        $inheritedData = [];
        $childMethod = null;
        $overrideData = null;
        $implementationData = [];

        if (isset($class['methods'][$parentMethodData['name']])) {
            $childMethod = $class['methods'][$parentMethodData['name']];

            if ($class['type'] !== 'interface' && $parentMethodData['declaringStructure']['type'] === 'interface') {
                $implementationData = array_merge($childMethod['implementations'], [
                    [
                        'declaringClass'     => $parentMethodData['declaringClass'],
                        'declaringStructure' => $parentMethodData['declaringStructure'],
                        'range'              => $parentMethodData['range'],
                    ],
                ]);
            } else {
                $overrideData = [
                    'declaringClass'     => $parentMethodData['declaringClass'],
                    'declaringStructure' => $parentMethodData['declaringStructure'],
                    'range'              => $parentMethodData['range'],
                    'wasAbstract'        => $parentMethodData['isAbstract'],
                ];
            }

            if ($parentMethodData['hasDocumentation'] && $this->isInheritingFullDocumentation($childMethod)) {
                $inheritedData = $this->extractInheritedMethodInfo($parentMethodData, $childMethod);
            } elseif ($childMethod['longDescription'] !== null && $parentMethodData['longDescription'] !== null) {
                $inheritedData['longDescription'] = $this->resolveInheritDoc(
                    $childMethod['longDescription'],
                    $parentMethodData['longDescription']
                );
            }
        } else {
            $childMethod = [];
        }

        $class['methods'][$parentMethodData['name']] = array_merge($parentMethodData, $childMethod, $inheritedData, [
            'override'        => $overrideData,
            'implementations' => $implementationData,
        ]);
    }
}
