<?php

namespace Serenata\PrettyPrinting;

use PhpParser\PrettyPrinter;

/**
 * Pretty printer extensions that can handle our custom nodes.
 */
final class NodePrettyPrinter extends PrettyPrinter\Standard
{
    // phpcs:disable
    public function parsing_Node_Keyword_Static(): string
    {
        return 'static';
    }

    public function parsing_Node_Keyword_Self(): string
    {
        return 'self';
    }

    public function parsing_Node_Keyword_Parent(): string
    {
        return 'parent';
    }
    // phpcs:enable
}
