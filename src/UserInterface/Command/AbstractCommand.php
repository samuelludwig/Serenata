<?php

namespace Serenata\UserInterface\Command;

/**
 * Base class for commands.
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Retrieves the byte offset from the specified character offset in the specified string. The result will always be
     * larger than or equal to the passed in value, depending on the amount of multi-byte characters encountered.
     *
     * @param int    $characterOffset
     * @param string $string
     *
     * @deprecated All new commands should only support Position objects containing character offsets as per the
     *             language server protocol. Only kept for backwards compatibility.
     *
     * @return int
     */
    final protected function getByteOffsetFromCharacterOffset(int $characterOffset, string $string): int
    {
        return strlen(mb_substr($string, 0, $characterOffset));
    }
}
