<?php
namespace Slim;

use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

class NotFoundHandler
{
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->withBody(new Http\Body(fopen('php://temp', 'r+')))
            ->write($this->generateTemplateMarkup(
                '404 Page Not Found',
                '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled ' .
                'correctly. If all else fails, you can visit our home page at the link below.</p><a href="' .
                $request->getUri()->getBasePath() . '">Visit the Home Page</a>'
            ));
    }

    /**
     * Generate diagnostic template markup
     *
     * This method accepts a title and body content to generate an HTML document layout.
     *
     * @param  string $title The title of the HTML template
     * @param  string $body  The body content of the HTML template
     * @return string
     */
    protected function generateTemplateMarkup($title, $body)
    {
        return sprintf(
            "<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;" .
            "width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $body
        );
    }
}
