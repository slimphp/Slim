<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Error\Renderers;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Interfaces\ExceptionRendererInterface;
use Throwable;

use function get_class;
use function sprintf;

/**
 * Formats exceptions into a HTML response.
 */
final class HtmlExceptionRenderer implements ExceptionRendererInterface
{
    use ExceptionRendererTrait;

    private StreamFactoryInterface $streamFactory;

    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?Throwable $exception = null,
        bool $displayErrorDetails = false
    ): ResponseInterface {
        if ($displayErrorDetails) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderExceptionFragment($exception);
        } else {
            $html = sprintf('<p>%s</p>', $this->getErrorDescription($exception));
        }

        $html = $this->renderHtmlBody($this->getErrorTitle($exception), $html);

        $body = $this->streamFactory->createStream($html);
        $response = $response->withBody($body);

        return $response->withHeader('Content-Type', 'text/html');
    }

    private function renderExceptionFragment(Throwable $exception): string
    {
        $html = sprintf(
            '<div><strong>Type:</strong> %s</div>',
            $this->escapeHtml(get_class($exception))
        );

        $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();
        $html .= sprintf('<div><strong>Code:</strong> %s</div>', $this->escapeHtml((string)$code));

        $html .= sprintf(
            '<div><strong>Message:</strong> %s</div>',
            $this->escapeHtml($exception->getMessage())
        );

        $html .= sprintf(
            '<div><strong>File:</strong> %s</div>',
            $this->escapeHtml($exception->getFile())
        );

        $html .= sprintf(
            '<div><strong>Line:</strong> %s</div>',
            $this->escapeHtml((string)$exception->getLine())
        );

        $html .= '<h2>Trace</h2>';
        $html .= sprintf('<pre>%s</pre>', $this->escapeHtml($exception->getTraceAsString()));

        return $html;
    }

    public function renderHtmlBody(string $title = '', string $html = ''): string
    {
        return sprintf(
            '<!doctype html>' .
            '<html lang="en">' .
            '    <head>' .
            '        <meta charset="utf-8">' .
            '        <meta name="viewport" content="width=device-width, initial-scale=1">' .
            '        <title>%s</title>' .
            '        <style>' .
            '            body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif}' .
            '            h1{margin:0;font-size:48px;font-weight:normal;line-height:48px}' .
            '            strong{display:inline-block;width:65px}' .
            '            a{color:#007BFF;text-decoration:none}' .
            '            a:hover{text-decoration:underline}' .
            '        </style>' .
            '    </head>' .
            '    <body>' .
            '        <h1>%s</h1>' .
            '        <div>%s</div>' .
            '        <a href="#" onclick="window.history.go(-1); return false;">Go Back</a>' .
            '    </body>' .
            '</html>',
            $this->escapeHtml($title),
            $this->escapeHtml($title),
            $html
        );
    }

    private function escapeHtml(?string $input = null): string
    {
        return htmlspecialchars($input ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
