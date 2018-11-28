<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;

class XmlPhpErrorMessage implements RenderableInterface
{

    private $displayErrorDetails;

    public function render($args)
    {
        $this->displayErrorDetails = $args[1];
        return $this->renderXmlErrorMessage($args[0]);
    }


     /**
     * Render XML error
     *
     * @param \Throwable $error
     *
     * @return string
     */
    protected function renderXmlErrorMessage(\Throwable $error)
    {
        $xml = "<error>\n  <message>Slim Application Error</message>\n";
        if ($this->displayErrorDetails) {
            do {
                $xml .= "  <error>\n";
                $xml .= "    <type>" . get_class($error) . "</type>\n";
                $xml .= "    <code>" . $error->getCode() . "</code>\n";
                $xml .= "    <message>" . $this->createCdataSection($error->getMessage()) . "</message>\n";
                $xml .= "    <file>" . $error->getFile() . "</file>\n";
                $xml .= "    <line>" . $error->getLine() . "</line>\n";
                $xml .= "    <trace>" . $this->createCdataSection($error->getTraceAsString()) . "</trace>\n";
                $xml .= "  </error>\n";
            } while ($error = $error->getPrevious());
        }
        $xml .= "</error>";

        return $xml;
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