<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 16/07/15
 * Time: 18:08
 */
namespace Slim;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\Http\EnvironmentInterface;
use Slim\Interfaces\InvocationStrategyInterface;


/**
 * Slim's interface referencing configurable services.
 *
 * You can use classes implementing this interface to pass special services to Slim\App.
 */
interface ConfigurationInterface
{
    /**
     * @return mixed
     */
    public function getSettings();

    /**
     * @return EnvironmentInterface
     */
    public function getEnvironment();

    /**
     * @return ServerRequestInterface
     */
    public function getRequest();

    /**
     * @return mixed
     */
    public function getResponse();

    /**
     * @return mixed
     */
    public function getRouter();

    /**
     * @return InvocationStrategyInterface
     */
    public function getFoundHandler();

    /**
     * @return callable
     */
    public function getErrorHandler();

    /**
     * @return callable
     */
    public function getNotFoundHandler();

    /**
     * @return callable
     */
    public function getNotAllowedHandler();

    /**
     * @return CallableResolverInterface
     */
    public function getCallableResolver();

    /**
     * @return ContainerInterface
     */
    public function getContainer();
}