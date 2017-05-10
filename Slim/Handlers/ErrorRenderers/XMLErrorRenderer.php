<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Handlers;

/**
 * Default Slim application XML Error Renderer
 */
class XMLErrorRenderer extends AbstractErrorRenderer
{
    /**
     * @return string
     */
    public function renderThrowableOutput()
    {
        $e = $this->exception;
        $xml = "<error>\n  <message>Slim Application Error</message>\n";
        if ($this->displayErrorDetails) {
            do {
                $xml .= "  <exception>\n";
                $xml .= "    <type>" . get_class($e) . "</type>\n";
                $xml .= "    <code>" . $e->getCode() . "</code>\n";
                $xml .= "    <message>" . $this->createCdataSection($e->getMessage()) . "</message>\n";
                $xml .= "    <file>" . $e->getFile() . "</file>\n";
                $xml .= "    <line>" . $e->getLine() . "</line>\n";
                $xml .= "    <trace>" . $this->createCdataSection($e->getTraceAsString()) . "</trace>\n";
                $xml .= "  </exception>\n";
            } while ($e = $e->getPrevious());
        }
        $xml .= "</error>";

        return $xml;
    }

    /**
     * @return string
     */
    public function renderGenericOutput()
    {
        return "<root><message>{$this->exception->getMessage()}</message></root>";
    }

    /**
     * Returns a CDATA section with the given content.
     *
     * @param  string $content
     * @return string
     */
    private function createCdataSection($content)
    {
        return sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $content));
    }
}