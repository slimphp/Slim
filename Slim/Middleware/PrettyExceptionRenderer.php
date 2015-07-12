<?php


namespace Slim\Middleware;


use Exception;

class PrettyExceptionRenderer
{

    /**
     * @param Exception $exception
     * @return string
     */
    public static function renderException(Exception $exception)
    {
        $title = 'Slim Application Error';
        $code = $exception->getCode();
        $message = htmlspecialchars($exception->getMessage());
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = str_replace(array('#', "\n"), array('<div>#', '</div>'), htmlspecialchars($exception->getTraceAsString()));
        $html = sprintf('<h1>%s</h1>', $title);
        $html .= '<p>The application could not run because of the following error:</p>';
        $html .= '<h2>Details</h2>';
        $html .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));
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

        return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html);
    }
}