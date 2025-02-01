<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Builder;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Slim\App;
use Slim\Container\DefaultDefinitions;
use Slim\Container\HttpDefinitions;
use Slim\Container\PhpDiContainerFactory;
use Slim\Interfaces\ContainerFactoryInterface;

/**
 * This class is responsible for building and configuring a Slim application with a dependency injection (DI) container.
 * It provides methods to set up service definitions, configure a custom container factory, and more.
 *
 * Key functionalities include:
 * - Building the Slim `App` instance with configured dependencies.
 * - Customizing the DI container with user-defined service definitions or a custom container factory.
 */
final class AppBuilder
{
    /**
     * @var array Service definitions for the DI container
     */
    private array $definitions = [];

    /**
     * @var ContainerFactoryInterface|null Factory function for creating a custom DI container
     */
    private ?ContainerFactoryInterface $containerFactory = null;

    /**
     * The constructor.
     *
     * Initializes the builder with the default service definitions.
     */
    public function __construct()
    {
        $this->addDefinitionsClass(DefaultDefinitions::class);
        $this->addDefinitionsClass(HttpDefinitions::class);
    }

    /**
     * Builds the Slim application instance using the configured DI container.
     *
     * @return App The fully built Slim application instance
     */
    public function build(): App
    {
        return $this->buildContainer()->get(App::class);
    }

    /**
     * Sets the service definitions for the DI container.
     *
     * @param array $definitions An array of service definitions
     *
     * @return self The current instance
     */
    public function addDefinitions(array $definitions): self
    {
        $this->definitions = array_merge($this->definitions, $definitions);

        return $this;
    }

    /**
     * Sets the service definitions for the DI container.
     *
     * @param string $class A definition provider class name
     *
     * @throws RuntimeException
     *
     * @return self The current instance
     */
    public function addDefinitionsClass(string $class): self
    {
        $definitions = call_user_func(new $class());

        if (!is_array($definitions)) {
            throw new RuntimeException('Definition file should return an array of definitions');
        }

        $this->addDefinitions($definitions);

        return $this;
    }

    /**
     * Sets the service definitions for the DI container.
     *
     * @param string $file A service definitions provider file
     *
     * @throws RuntimeException
     *
     * @return self The current instance
     */
    public function addDefinitionsFile(string $file): self
    {
        $definitions = require $file;

        if (!is_array($definitions)) {
            throw new RuntimeException('Definition file should return an array of definitions');
        }

        $this->addDefinitions($definitions);

        return $this;
    }

    /**
     * Sets a custom factory for creating the DI container.
     *
     * @param ContainerFactoryInterface $containerFactory A DI container factory
     *
     * @return self The current instance
     */
    public function setContainerFactory(ContainerFactoryInterface $containerFactory): self
    {
        $this->containerFactory = $containerFactory;

        return $this;
    }

    /**
     * Creates and configures the DI container.
     *
     * If a custom container factory is set, it will be used to create the container;
     * otherwise, a default container with the provided definitions will be created.
     *
     * @return ContainerInterface The configured DI container
     */
    private function buildContainer(): ContainerInterface
    {
        $this->containerFactory = $this->containerFactory ?? new PhpDiContainerFactory();

        return $this->containerFactory->createContainer($this->definitions);
    }
}
