<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Tests\Providers\PSR7ObjectProvider;

class MockRequestHandler implements RequestHandlerInterface
{
    /**
     * @var int
     */
    private $calledCount = 0;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        $responseFactory = $psr7ObjectProvider->getResponseFactory();

        $this->calledCount += 1;
        return $responseFactory->createResponse();
    }

    /**
     * @return int
     */
    public function getCalledCount(): int
    {
        return $this->calledCount;
    }
}
