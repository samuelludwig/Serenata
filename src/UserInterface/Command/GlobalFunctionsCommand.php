<?php

namespace PhpIntegrator\UserInterface\Command;

use PhpIntegrator\Analysis\FunctionListProviderInterface;

use PhpIntegrator\Sockets\JsonRpcRequest;
use PhpIntegrator\Sockets\JsonRpcResponseSenderInterface;

/**
 * Command that shows a list of global functions.
 */
class GlobalFunctionsCommand extends AbstractCommand
{
    /**
     * @var FunctionListProviderInterface
     */
    private $functionListProvider;

    /**
     * @param FunctionListProviderInterface $functionListProvider
     */
    public function __construct(FunctionListProviderInterface $functionListProvider)
    {
        $this->functionListProvider = $functionListProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(JsonRpcRequest $request, JsonRpcResponseSenderInterface $jsonRpcResponseSender)
    {
        return $this->getGlobalFunctions();
    }

     /**
      * @return array
      */
    public function getGlobalFunctions(): array
    {
        return $this->functionListProvider->getAll();
    }
}
