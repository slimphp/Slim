<?php
namespace Slim;

class ErrorHandler
{
    protected $app;

    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
    }

    public function __invoke($arg = null)
    {
        $this->app['response']->setStatus(500);
        $this->app['response']->setHeader('Content-type', 'text/html');

        if ($this->app['mode'] === 'development') {
            $title = 'Slim Application Error';
            $html = '';

            if ($arg instanceof \Exception) {
                $code = $arg->getCode();
                $message = $arg->getMessage();
                $file = $arg->getFile();
                $line = $arg->getLine();
                $trace = str_replace(array('#', '\n'), array('<div>#', '</div>'), $arg->getTraceAsString());

                $html = '<p>The application could not run because of the following error:</p>';
                $html .= '<h2>Details</h2>';
                $html .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($arg));
                if ($code) {
                    $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
                }
                if ($message) {
                    $html .= sprintf('<div><strong>Message:</strong> %s</div>', $message);
                }
                if ($file) {
                    $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
                }
                if ($line) {
                    $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
                }
                if ($trace) {
                    $html .= '<h2>Trace</h2>';
                    $html .= sprintf('<pre>%s</pre>', $trace);
                }
            } else {
                $html = sprintf('<p>%s</p>', (string)$arg);
            }

            return $this->generateTemplateMarkup($title, $html);
        } else {
            return $this->generateTemplateMarkup(
                'Error',
                '<p>A website error has occurred. The website administrator has been notified of the issue. Sorry'
                . 'for the temporary inconvenience.</p>'
            );
        }
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
