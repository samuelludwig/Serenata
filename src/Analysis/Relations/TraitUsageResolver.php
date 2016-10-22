<?php

namespace PhpIntegrator\Analysis\Relations;

use ArrayObject;

/**
 * Deals with resolving trait usage for classlikes.
 */
class TraitUsageResolver extends AbstractResolver
{
    /**
     * @param ArrayObject $trait
     * @param ArrayObject $class
     * @param array       $traitAliases
     * @param array       $traitPrecedences
     *
     * @return ArrayObject
     */
    public function resolveUseOf(ArrayObject $trait, ArrayObject $class, array $traitAliases, array $traitPrecedences)
    {
        foreach ($trait['properties'] as $property) {
            $this->resolveTraitUseOfProperty($property, $class);
        }

        foreach ($trait['methods'] as $method) {
            // If the method was aliased, pretend it has another name and access modifier before "inheriting" it.
            if (isset($traitAliases[$method['name']])) {
                $alias = $traitAliases[$method['name']];

                if ($alias['trait_fqcn'] === null || $alias['trait_fqcn'] === $trait['name']) {
                    $method['name']        = $alias['alias'] ?: $method['name'];
                    $method['isPublic']    = ($alias['access_modifier'] === 'public');
                    $method['isProtected'] = ($alias['access_modifier'] === 'protected');
                    $method['isPrivate']   = ($alias['access_modifier'] === 'private');
                }
            }

            if (isset($traitPrecedences[$method['name']])) {
                if ($traitPrecedences[$method['name']]['trait_fqcn'] !== $trait['name']) {
                    // The method is present in multiple used traits and precedences indicate that the one
                    // from this trait should not be imported.
                    continue;
                }
            }

            $this->resolveTraitUseOfMethod($method, $class);
        }
    }

    /**
     * @param array       $traitPropertyData
     * @param ArrayObject $class
     */
    protected function resolveTraitUseOfProperty(array $traitPropertyData, ArrayObject $class)
    {
        $inheritedData = [];
        $childProperty = null;
        $overriddenPropertyData = null;

        if (isset($class['properties'][$traitPropertyData['name']])) {
            $childProperty = $class['properties'][$traitPropertyData['name']];

            $overriddenPropertyData = [
                'declaringClass'     => $childProperty['declaringClass'],
                'declaringStructure' => $traitPropertyData['declaringStructure'],
                'startLine'          => $traitPropertyData['startLine'],
                'endLine'            => $traitPropertyData['endLine']
            ];

            if ($traitPropertyData['hasDocumentation'] && $this->isInheritingFullDocumentation($childProperty)) {
                $inheritedData = $this->extractInheritedPropertyInfo($traitPropertyData);
            } else {
                $inheritedData['longDescription'] = $this->resolveInheritDoc(
                    $childProperty['longDescription'],
                    $traitPropertyData['longDescription']
                );
            }

            $childProperty['declaringStructure'] = [
                'name'            => $traitPropertyData['declaringStructure']['name'],
                'filename'        => $traitPropertyData['declaringStructure']['filename'],
                'startLine'       => $traitPropertyData['declaringStructure']['startLine'],
                'endLine'         => $traitPropertyData['declaringStructure']['endLine'],
                'type'            => $traitPropertyData['declaringStructure']['type'],
                'startLineMember' => $traitPropertyData['startLine'],
                'endLineMember'   => $traitPropertyData['endLine']
            ];
        } else {
            $childProperty = [];
        }

        $class['properties'][$traitPropertyData['name']] = array_merge($traitPropertyData, $childProperty, $inheritedData, [
            'override' => $overriddenPropertyData,

            'declaringClass' => [
                'name'            => $class['name'],
                'filename'        => $class['filename'],
                'startLine'       => $class['startLine'],
                'endLine'         => $class['endLine'],
                'type'            => $class['type']
            ]
        ]);
    }

    /**
     * @param array       $traitMethodData
     * @param ArrayObject $class
     */
    protected function resolveTraitUseOfMethod(array $traitMethodData, ArrayObject $class)
    {
        $inheritedData = [];
        $childMethod = null;
        $overrideData = null;
        $implementationData = null;

        if (isset($class['methods'][$traitMethodData['name']])) {
            $childMethod = $class['methods'][$traitMethodData['name']];

            if ($traitMethodData['declaringStructure']['type'] === 'interface') {
                $implementationData = [
                    'declaringClass'     => $childMethod['declaringClass'],
                    'declaringStructure' => $traitMethodData['declaringStructure'],
                    'startLine'          => $traitMethodData['startLine'],
                    'endLine'            => $traitMethodData['endLine']
                ];
            } else {
                $overrideData = [
                    'declaringClass'     => $childMethod['declaringClass'],
                    'declaringStructure' => $traitMethodData['declaringStructure'],
                    'startLine'          => $traitMethodData['startLine'],
                    'endLine'            => $traitMethodData['endLine'],
                    'wasAbstract'        => $traitMethodData['isAbstract']
                ];
            }

            if ($traitMethodData['hasDocumentation'] && $this->isInheritingFullDocumentation($childMethod)) {
                $inheritedData = $this->extractInheritedMethodInfo($traitMethodData, $childMethod);
            } else {
                $inheritedData['longDescription'] = $this->resolveInheritDoc(
                    $childMethod['longDescription'],
                    $traitMethodData['longDescription']
                );
            }

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

        $class['methods'][$traitMethodData['name']] = array_merge($traitMethodData, $childMethod, $inheritedData, [
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
