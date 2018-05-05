<?php

namespace Serenata\Tooltips;

use AssertionError;

/**
 * Generates tooltips for classlikes.
 */
class ClassLikeTooltipGenerator
{
    /**
     * @param array $info
     *
     * @return string
     */
    public function generate(array $info): string
    {
        $sections = [
            $this->generateSummary($info),
            $this->generateLongDescription($info),
            $this->generateFullName($info),
            $this->generateType($info)
        ];

        return implode("\n\n", array_filter($sections));
    }

    /**
     * @param array $info
     *
     * @return string
     */
    private function generateSummary(array $info): string
    {
        if ($info['shortDescription']) {
            return $info['shortDescription'];
        }

        return '(No documentation available)';
    }

    /**
     * @param array $info
     *
     * @return string|null
     */
    private function generateLongDescription(array $info): ?string
    {
        if (!empty($info['longDescription'])) {
            return "# Description\n" . $info['longDescription'];
        }

        return null;
    }

    /**
     * @param array $info
     *
     * @return string
     */
    private function generateFullName(array $info): string
    {
        return "# Full Name\n*{$info['fqcn']}*";
    }

    /**
     * @param array $info
     *
     * @return string
     */
    private function generateType(array $info): string
    {
        $type = null;

        if ($info['type'] === 'class') {
            if ($info['isAbstract']) {
                $type = 'Abstract class';
            } else {
                $type = 'Class';
            }
        } elseif ($info['type'] === 'trait') {
            $type = 'Trait';
        } elseif ($info['type'] === 'interface') {
            $type = 'Interface';
        } else {
            throw new AssertionError('Unknown type "' . $info['type'] . '" for classlike encountered');
        }

        return "# Type\n{$type}";
    }
}
