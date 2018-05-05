<?php

namespace Serenata\Parsing;

use League\HTMLToMarkdown;

/**
 * Generates a {@see HTMLToMarkdown\HtmlConverter} for converting HTML in docblocks to Markdown.
 */
class DocblockHtmlToMarkdownConverterFactory
{
    /**
     * @return HTMLToMarkdown\HtmlConverter
     */
    public function create(): HTMLToMarkdown\HtmlConverter
    {
        $environment = new HTMLToMarkdown\Environment();

        $environment->addConverter(new HTMLToMarkdown\Converter\BlockquoteConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\CodeConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\CommentConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\DivConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\EmphasisConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\HardBreakConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\HeaderConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\HorizontalRuleConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\ImageConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\LinkConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\ListBlockConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\ListItemConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\ParagraphConverter());
        $environment->addConverter(new HTMLToMarkdown\Converter\PreformattedConverter());

        $environment->getConfig()->merge([
            'header_style'    => 'setext',
            'suppress_errors' => true,
            'strip_tags'      => true,
            'bold_style'      => '**',
            'italic_style'    => '_',
            'remove_nodes'    => '',
            'hard_break'      => false,
            'list_item_style' => '-'
        ]);

        return new HTMLToMarkdown\HtmlConverter($environment);
    }
}
