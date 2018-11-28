<?php
namespace Slim\Handlers\Renderables;

use Slim\Interfaces\RenderableInterface;

class XmlErrorMessage implements RenderableInterface
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
     * @param \Exception $exception
     *
     * @return string
     */
    protected function renderXmlErrorMessage(\Exception $exception)
    {
        $xml = "<error>\n  <message>Slim Application Error</message>\n";
        if ($this->displayErrorDetails) {
            do {
                $xml .= "  <exception>\n";
                $xml .= "    <type>" . get_class($exception) . "</type>\n";
                $xml .= "    <code>" . $exception->getCode() . "</code>\n";
                $xml .= "    <message>" . $this->createCdataSection($exception->getMessage()) . "</message>\n";
                $xml .= "    <file>" . $exception->getFile() . "</file>\n";
                $xml .= "    <line>" . $exception->getLine() . "</line>\n";
                $xml .= "    <trace>" . $this->createCdataSection($exception->getTraceAsString()) . "</trace>\n";
                $xml .= "  </exception>\n";
            } while ($exception = $exception->getPrevious());
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