<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

/**
 * Default Slim application not allowed handler
 *
 * It outputs a simple message in either JSON, XML or HTML based on the
 * Accept header.
 */
class NotAllowed
{
    /**
     * Invoke error handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     * @param  string[]               $methods  Allowed HTTP methods
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        $allow = implode(', ', $methods);

        if ($request->getMethod() === 'OPTIONS') {
            $status = 200;
            $contentType = 'text/plain';
            $output = 'Allowed methods: ' . $allow;
        } else {
            $status = 405;
            $contentType = $this->determineContentType($request->getHeaderLine('Accept'));
            switch ($contentType) {
                case 'application/json':
                    $output = '{"message":"Method not allowed. Must be one of: ' . $allow . '"}';
                    break;

                case 'application/xml':
                    $output = "<root><message>Method not allowed. Must be one of: $allow</message></root>";
                    break;

                case 'text/html':
                default:
                    $contentType = 'text/html';
                    $output = <<<END
<html>
    <head>
        <title>Method not allowed</title>
        <style>
            body{
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1{
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
        </style>
    </head>
    <body>
        <h1>Method not allowed</h1>
        <p>Method not allowed. Must be one of: <strong>$allow</strong></p>
    </body>
</html>
END;
                    break;
            }
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response
                ->withStatus($status)
                ->withHeader('Content-type', $contentType)
                ->withHeader('Allow', $allow)
                ->withBody($body);
    }

    /**
     * Read the accept header and determine which content type we know about
     * is wanted.
     *
     * @param  string $acceptHeader Accept header from request
     * @return string
     */
    private function determineContentType($acceptHeader)
    {
        $list = explode(',', $acceptHeader);
        $known = ['application/json', 'application/xml', 'text/html'];
        
        foreach ($list as $type) {
            if (in_array($type, $known)) {
                return $type;
            }
        }

        return 'text/html';
    }
}
