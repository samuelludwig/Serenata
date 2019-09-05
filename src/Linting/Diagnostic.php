<?php

namespace Serenata\Linting;

use JsonSerializable;

use Serenata\Common\Range;

/**
 * Represents a diagnostic inside a resource.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#diagnostic
 */
final class Diagnostic implements JsonSerializable
{
    /**
     * @var Range
     */
    private $range;

    /**
     * @var int|null
     */
    private $severity;

    /**
     * @var int|string|null
     */
    private $code;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array|null
     */
    private $relatedInformation;

    /**
     * @param Range           $range
     * @param int|null        $severity
     * @param int|string|null $code
     * @param string|null     $source
     * @param string          $message
     * @param array|null      $relatedInformation
     */
    public function __construct(
        Range $range,
        ?int $severity,
        $code,
        ?string $source,
        string $message,
        ?array $relatedInformation
    ) {
        $this->range = $range;
        $this->severity = $severity;
        $this->code = $code;
        $this->source = $source;
        $this->message = $message;
        $this->relatedInformation = $relatedInformation;
    }

    /**
     * @return Range
     */
    public function getRange(): Range
    {
        return $this->range;
    }

    /**
     * @return int|null
     */
    public function getSeverity(): ?int
    {
        return $this->severity;
    }

    /**
     * @return int|string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array|null
     */
    public function getRelatedInformation(): ?array
    {
        return $this->relatedInformation;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'range'              => $this->getRange(),
            'severity'           => $this->getSeverity(),
            'code'               => $this->getCode(),
            'source'             => $this->getSource(),
            'message'            => $this->getMessage(),
            'relatedInformation' => $this->getRelatedInformation(),
        ];
    }
}
