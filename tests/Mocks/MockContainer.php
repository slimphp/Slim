<?php declare(strict_types=1);

namespace Slim\Tests\Mocks;

use Psr\Container\ContainerInterface;

class MockContainer implements ContainerInterface
{
    /** @var array */
    private $entries;

    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public function get($id)
    {
        return $this->entries[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->entries);
    }
}
