<?php
/**
 * Created by PhpStorm.
 * User: Glenn
 * Date: 2016-03-18
 * Time: 3:43 PM
 */

namespace Slim\Views;


use Psr\Http\Message\ResponseInterface;

interface RendererInterface extends \ArrayAccess
{

    public function fetch($template, $data = []);

    public function render(ResponseInterface $response, $template, $data = []);

}