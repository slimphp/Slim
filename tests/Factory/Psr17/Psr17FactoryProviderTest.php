<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory\Psr17;

use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Tests\TestCase;

class Psr17FactoryProviderTest extends TestCase
{
    public function testGetSetFactories()
    {
        Psr17FactoryProvider::setFactories([]);

        $this->assertEquals([], Psr17FactoryProvider::getFactories());
    }


    public function testAddFactory()
    {
        Psr17FactoryProvider::setFactories(['Factory 1']);
        Psr17FactoryProvider::addFactory('Factory 2');

        $this->assertEquals(['Factory 2', 'Factory 1'], Psr17FactoryProvider::getFactories());
    }
}
