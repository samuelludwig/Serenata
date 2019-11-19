<?php

namespace Serenata\Utility;

use JsonSerializable;

/**
 * Represents initialization parameters.
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#initialize
 */
final class ServerCapabilities implements JsonSerializable
{
    /**
     * @var TextDocumentSyncOptions|int|null
     */
    private $textDocumentSync;

    /**
     * @var bool|null
     */
    private $hoverProvider;

    /**
     * @var CompletionOptions|null
     */
    private $completionProvider;

    /**
     * @var SignatureHelpOptions|null
     */
    private $signatureHelpProvider;

    /**
     * @var bool|null
     */
    private $definitionProvider;

    /**
     * @var bool|object|null
     */
    private $typeDefinitionProvider;

    /**
     * @var bool|object|null
     */
    private $implementationProvider;

    /**
     * @var bool|null
     */
    private $referencesProvider;

    /**
     * @var bool|null
     */
    private $documentHighlightProvider;

    /**
     * @var bool|null
     */
    private $documentSymbolProvider;

    /**
     * @var bool|null
     */
    private $workspaceSymbolProvider;

    /**
     * @var bool|object|null
     */
    private $codeActionProvider;

    /**
     * @var bool|object|null
     */
    private $codeLensProvider;

    /**
     * @var bool|null
     */
    private $documentFormattingProvider;

    /**
     * @var bool|null
     */
    private $documentRangeFormattingProvider;

    /**
     * @var object|null
     */
    private $documentOnTypeFormattingProvider;

    /**
     * @var bool|object|null
     */
    private $renameProvider;

    /**
     * @var object|null
     */
    private $documentLinkProvider;

    /**
     * @var bool|object|null
     */
    private $colorProvider;

    /**
     * @var bool|object|null
     */
    private $foldingRangeProvider;

    /**
     * @var object|null
     */
    private $executeCommandProvider;

    /**
     * @var array|null
     */
    private $workspace;

    /**
     * @var mixed|null
     */
    private $experimental;

    /**
     * @param TextDocumentSyncOptions|int|null $textDocumentSync
     * @param bool|null                        $hoverProvider
     * @param CompletionOptions|null           $completionProvider
     * @param SignatureHelpOptions|null        $signatureHelpProvider
     * @param bool|null                        $definitionProvider
     * @param bool|object|null                 $typeDefinitionProvider
     * @param bool|object|null                 $implementationProvider
     * @param bool|null                        $referencesProvider
     * @param bool|null                        $documentHighlightProvider
     * @param bool|null                        $documentSymbolProvider
     * @param bool|null                        $workspaceSymbolProvider
     * @param bool|object|null                 $codeActionProvider
     * @param bool|object|null                 $codeLensProvider
     * @param bool|null                        $documentFormattingProvider
     * @param bool|null                        $documentRangeFormattingProvider
     * @param object|null                      $documentOnTypeFormattingProvider
     * @param bool|object|null                 $renameProvider
     * @param object|null                      $documentLinkProvider
     * @param bool|object|null                 $colorProvider
     * @param bool|object|null                 $foldingRangeProvider
     * @param object|null                      $executeCommandProvider
     * @param array|null                       $workspace
     * @param mixed|null                       $experimental
     */
    public function __construct(
        $textDocumentSync,
        ?bool $hoverProvider,
        $completionProvider,
        $signatureHelpProvider,
        ?bool $definitionProvider,
        $typeDefinitionProvider,
        $implementationProvider,
        ?bool $referencesProvider,
        ?bool $documentHighlightProvider,
        ?bool $documentSymbolProvider,
        ?bool $workspaceSymbolProvider,
        $codeActionProvider,
        $codeLensProvider,
        ?bool $documentFormattingProvider,
        ?bool $documentRangeFormattingProvider,
        $documentOnTypeFormattingProvider,
        $renameProvider,
        $documentLinkProvider,
        $colorProvider,
        $foldingRangeProvider,
        $executeCommandProvider,
        ?array $workspace,
        $experimental
    ) {
        $this->textDocumentSync = $textDocumentSync;
        $this->hoverProvider = $hoverProvider;
        $this->completionProvider = $completionProvider;
        $this->signatureHelpProvider = $signatureHelpProvider;
        $this->definitionProvider = $definitionProvider;
        $this->typeDefinitionProvider = $typeDefinitionProvider;
        $this->implementationProvider = $implementationProvider;
        $this->referencesProvider = $referencesProvider;
        $this->documentHighlightProvider = $documentHighlightProvider;
        $this->documentSymbolProvider = $documentSymbolProvider;
        $this->workspaceSymbolProvider = $workspaceSymbolProvider;
        $this->codeActionProvider = $codeActionProvider;
        $this->codeLensProvider = $codeLensProvider;
        $this->documentFormattingProvider = $documentFormattingProvider;
        $this->documentRangeFormattingProvider = $documentRangeFormattingProvider;
        $this->documentOnTypeFormattingProvider = $documentOnTypeFormattingProvider;
        $this->renameProvider = $renameProvider;
        $this->documentLinkProvider = $documentLinkProvider;
        $this->colorProvider = $colorProvider;
        $this->foldingRangeProvider = $foldingRangeProvider;
        $this->executeCommandProvider = $executeCommandProvider;
        $this->workspace = $workspace;
        $this->experimental = $experimental;
    }

    /**
     * @return TextDocumentSyncOptions|int|null
     */
    public function getTextDocumentSync()
    {
        return $this->textDocumentSync;
    }

    /**
     * @return bool|null
     */
    public function getHoverProvider(): ?bool
    {
        return $this->hoverProvider;
    }

    /**
     * @return CompletionOptions|null
     */
    public function getCompletionProvider(): ?CompletionOptions
    {
        return $this->completionProvider;
    }

    /**
     * @return SignatureHelpOptions|null
     */
    public function getSignatureHelpProvider(): ?SignatureHelpOptions
    {
        return $this->signatureHelpProvider;
    }

    /**
     * @return bool|null
     */
    public function getDefinitionProvider(): ?bool
    {
        return $this->definitionProvider;
    }

    /**
     * @return bool|object|null
     */
    public function getTypeDefinitionProvider()
    {
        return $this->typeDefinitionProvider;
    }

    /**
     * @return bool|object|null
     */
    public function getImplementationProvider()
    {
        return $this->implementationProvider;
    }

    /**
     * @return bool|null
     */
    public function getReferencesProvider(): ?bool
    {
        return $this->referencesProvider;
    }

    /**
     * @return bool|null
     */
    public function getDocumentHighlightProvider(): ?bool
    {
        return $this->documentHighlightProvider;
    }

    /**
     * @return bool|null
     */
    public function getDocumentSymbolProvider(): ?bool
    {
        return $this->documentSymbolProvider;
    }

    /**
     * @return bool|null
     */
    public function getWorkspaceSymbolProvider(): ?bool
    {
        return $this->workspaceSymbolProvider;
    }

    /**
     * @return bool|object|null
     */
    public function getCodeActionProvider()
    {
        return $this->codeActionProvider;
    }

    /**
     * @return bool|object|null
     */
    public function getCodeLensProvider()
    {
        return $this->codeLensProvider;
    }

    /**
     * @return bool|null
     */
    public function getDocumentFormattingProvider(): ?bool
    {
        return $this->documentFormattingProvider;
    }

    /**
     * @return bool|null
     */
    public function getDocumentRangeFormattingProvider(): ?bool
    {
        return $this->documentRangeFormattingProvider;
    }

    /**
     * @return object|null
     */
    public function getDocumentOnTypeFormattingProvider()
    {
        return $this->documentOnTypeFormattingProvider;
    }

    /**
     * @return bool|object|null
     */
    public function getRenameProvider()
    {
        return $this->renameProvider;
    }

    /**
     * @return object|null
     */
    public function getDocumentLinkProvider()
    {
        return $this->documentLinkProvider;
    }

    /**
     * @return bool|object|null
     */
    public function getColorProvider()
    {
        return $this->colorProvider;
    }

    /**
     * @return bool|object|null
     */
    public function getFoldingRangeProvider()
    {
        return $this->foldingRangeProvider;
    }

    /**
     * @return object|null
     */
    public function getExecuteCommandProvider()
    {
        return $this->executeCommandProvider;
    }

    /**
     * @return array|null
     */
    public function getWorkspace(): ?array
    {
        return $this->workspace;
    }

    /**
     * @return mixed|null
     */
    public function getExperimental()
    {
        return $this->experimental;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'textDocumentSync'                 => $this->getTextDocumentSync(),
            'hoverProvider'                    => $this->getHoverProvider(),
            'completionProvider'               => $this->getCompletionProvider(),
            'signatureHelpProvider'            => $this->getSignatureHelpProvider(),
            'definitionProvider'               => $this->getDefinitionProvider(),
            'typeDefinitionProvider'           => $this->getTypeDefinitionProvider(),
            'implementationProvider'           => $this->getImplementationProvider(),
            'referencesProvider'               => $this->getReferencesProvider(),
            'documentHighlightProvider'        => $this->getDocumentHighlightProvider(),
            'documentSymbolProvider'           => $this->getDocumentSymbolProvider(),
            'workspaceSymbolProvider'          => $this->getWorkspaceSymbolProvider(),
            'codeActionProvider'               => $this->getCodeActionProvider(),
            'codeLensProvider'                 => $this->getCodeLensProvider(),
            'documentFormattingProvider'       => $this->getDocumentFormattingProvider(),
            'documentRangeFormattingProvider'  => $this->getDocumentRangeFormattingProvider(),
            'documentOnTypeFormattingProvider' => $this->getDocumentOnTypeFormattingProvider(),
            'renameProvider'                   => $this->getRenameProvider(),
            'documentLinkProvider'             => $this->getDocumentLinkProvider(),
            'colorProvider'                    => $this->getColorProvider(),
            'foldingRangeProvider'             => $this->getFoldingRangeProvider(),
            'executeCommandProvider'           => $this->getExecuteCommandProvider(),
            'workspace'                        => $this->getWorkspace(),
            'experimental'                     => $this->getExperimental(),
        ];
    }
}
