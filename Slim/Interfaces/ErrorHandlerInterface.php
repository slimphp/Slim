<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Exception;
use Throwable;

/**
 * ErrorHandlerInterface
 *
 * @package Slim
 * @since   4.0.0
 */
interface ErrorHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param Exception|Throwable $exception
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        $exception,
        $displayErrorDetails,
        $logErrors,
        $logErrorDetails
    );
}
