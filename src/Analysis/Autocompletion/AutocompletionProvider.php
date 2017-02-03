<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Analysis\GlobalFunctionsProvider;

/**
 * Provides autocompletion suggestions at a specific location in a file.
 */
class AutocompletionProvider
{
    /**
     * @var GlobalFunctionsProvider
     */
    protected $globalFunctionsProvider;

    /**
     * @param GlobalFunctionsProvider $globalFunctionsProvider
     */
    public function __construct(GlobalFunctionsProvider $globalFunctionsProvider)
    {
        $this->globalFunctionsProvider = $globalFunctionsProvider;
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @return array
     */
    public function getSuggestions(string $code, int $offset): array
    {
        // $exampleSuggestion = [
        //     'filterText'    => null, // TODO: For text
        //     'kind'          => null, // TODO: For type
        //     'insertText'    => null, // TODO: For snippet
        //     'label'         => null, // TODO: For displayText
        //     'documentation' => null, // TODO: For description
        //
        //     'data' => [
        //         'isDeprecated'       => null, // TODO: For className
        //         'protectionLevel'    => null, // TODO: For leftLabel
        //         'declaringStructure' => null, // TODO: For rightLabelHTML
        //         'url'                => null, // TODO: For descriptionMoreURL
        //     ]
        // ];


        $suggestions = [];
        $suggestions = array_merge($suggestions, $this->getGlobalFunctionSuggestions());

        return $suggestions;
    }

    /**
     * @return array
     */
    protected function getGlobalFunctionSuggestions(): array
    {
        $suggestions = [];

        foreach ($this->globalFunctionsProvider->getAll() as $globalFunction) {
            $suggestions = $this->getGlobalFunctionSuggestionFromSuggestion($globalFunction);
        }

        return $suggestions;
    }

    /**
     * @param array $globalFunction
     *
     * @return array
     */
    protected function getGlobalFunctionSuggestionFromSuggestion(array $globalFunction): array
    {
        $insertText = $globalFunction['name'];
        $placeCursorBetweenParentheses = !empty($globalFunction['parameters']);


        if (true) {
            // TODO: If insertion position is followed by opening paraenthesis, don't add paraentheses at all.
            $insertText .= '()';
        }

        return [
            'filterText'    => $globalFunction['name'],
            'kind'          => SuggestionKind::FUNCTION,
            'insertText'    => $insertText,
            'label'         => $this->getFunctionLabel($globalFunction),
            'documentation' => $this->getFunctionDocumentation($globalFunction),

            'data' => [
                'isDeprecated'                  => $globalFunction['isDeprecated'],
                'protectionLevel'               => null, // TODO: For leftLabel
                'declaringStructure'            => null,
                'url'                           => $this->getFunctionUrl($globalFunction),
                'returnTypes'                   => $this->getFunctionReturnTypes($globalFunction),
                'placeCursorBetweenParentheses' => $placeCursorBetweenParentheses
            ]
        ];
    }

    /**
     * @param array $function
     *
     * @return string
     */
    protected function getFunctionLabel(array $function): string
    {
        /*
        getFunctionParameterList: (info) ->
            body = "("

            isInOptionalList = false

            for param, index in info.parameters
                description = ''
                description += '['   if param.isOptional and not isInOptionalList
                description += ', '  if index != 0
                description += '...' if param.isVariadic
                description += '&'   if param.isReference
                description += '$' + param.name
                description += ' = ' + param.defaultValue if param.defaultValue
                description += ']'   if param.isOptional and index == (info.parameters.length - 1)

                isInOptionalList = param.isOptional

                if not param.isOptional
                    body += description

                else
                    body += description

            body += ")"

            return body
            */
    }

    /**
     * @param array $function
     *
     * @return string
     */
    protected function getFunctionDocumentation(array $function): string
    {
        // shortDescription = ''
        //
        // if func.shortDescription? and func.shortDescription.length > 0
        //     shortDescription = func.shortDescription
        //
        // else if func.isBuiltin
        //     shortDescription = 'Built-in PHP function.'
    }

    /**
     * @param array $function
     *
     * @return string|null
     */
    protected function getFunctionProtectionLevel(array $function): ?string
    {
        /*
        if member.isPublic
            leftLabel += '<span class="icon icon-globe import">&nbsp;</span>'

        else if member.isProtected
            leftLabel += '<span class="icon icon-shield">&nbsp;</span>'

        else if member.isPrivate
            leftLabel += '<span class="icon icon-lock selector">&nbsp;</span>'
        */
    }

    /**
     * @param array $function
     *
     * @return string|null
     */
    protected function getFunctionUrl(array $function): ?string
    {
        // if func.isBuiltin then @config.get('php_documentation_base_urls').functions + func.name else null
    }

    /**
     * @param array $function
     *
     * @return string[]
     */
    protected function getFunctionReturnTypes(array $function): array
    {
        /*
        ###*
         * Retrieves the short name for the specified class name (i.e. the last segment, without the class namespace).
         *
         * @param {string} className
         *
         * @return {string}
        ###
        getClassShortName: (className) ->
            return null if not className

            parts = className.split('\\')
            return parts.pop()

        ###*
         * @param {Array} typeArray
         *
         * @return {String}
        ###
        getTypeSpecificationFromTypeArray: (typeArray) ->
            typeNames = typeArray.map (type) =>
                return @getClassShortName(type.type)

            return typeNames.join('|')
        */
    }
}
