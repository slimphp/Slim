<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Stack;

use Exception;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
* A simple middleware stack runner.
*
* Inspired by Relay: https://github.com/relayphp/Relay.Relay
* Copyright (c) 2015, Paul M. Jones, MIT License
*/
class Stack
{
    const ERR_RESPONSE = 'ResponseInterface instance expected';
    const ERR_RUNNING = 'Middleware cannot be added once the stack is running';
    const ERR_RESOLVER = 'Stack entry is not resolvable';

    /**
    * The internal middleware queue
    *
    * @var callable[]
    */
    protected $queue = [];

    /**
    * An optional array of callables to convert queue entries
    *
    * @var callable[]
    */
    protected $resolvers = [];

    /**
    * Whether the stack is dequeuing
    *
    * @var bool
    */
    protected $locked = false;

    /**
    * Create a new Stack runner
    *
    * @param mixed $kernel Optional core middleware which will be run last
    * @param callable[] $resolvers An optional array of callables to convert queue entries
    */
    public function __construct($kernel, array $resolvers = [])
    {
        if ($kernel !== null) {
            $this->queue[] = $kernel;
        }

        foreach ($resolvers as $resolver) {
            $this->addResolver($resolver);
        }
    }

    /**
    * Adds a middleware to the start of the queue
    *
    * @param mixed $entry A callable or entry that can be resolved
    */
    public function add($entry)
    {
        if ($this->locked) {
            throw new RuntimeException(self::ERR_RUNNING);
        }

        array_unshift($this->queue, $entry);
    }

    /**
    * Adds an array of middlewares to the start of the queue
    *
    * @param mixed[] $queue An array of callables or entries to be resolved
    */
    public function addQueue(array $queue)
    {
        foreach ($queue as $entry) {
            $this->add($entry);
        }
    }

    /**
    * Adds a resolver to the end of the internal resolvers array
    *
    * A resolver is a callable that is used to resolve a non-callable
    * queue entry into a middleware callable
    *
    * @param mixed $resolver
    */
    public function addResolver(callable $resolver)
    {
        $this->resolvers[] = $resolver;
    }

    /**
    * Calls all the middleware on the stack
    *
    * @param ServerRequestInterface $request
    * @param ResponseInterface $response
    * @return ResponseInterface
    */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->locked = true;
        $result = $this($request, $response);
        $this->locked = false;

        return $result;
    }

    /**
    * Calls the next entry from the start of the queue
    *
    * @param ServerRequestInterface $request
    * @param ResponseInterface $response
    * @return ResponseInterface
    *
    * @throws StackException
    * @throws Exception
    */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$this->queue) {
            return $response;
        }

        try {
            $next = $this->resolve(array_shift($this->queue));
            $result = call_user_func($next, $request, $response, $this);

            if ($result instanceof ResponseInterface === false) {
                throw new RuntimeException(self::ERR_RESPONSE);
            }
            return $result;

        } catch (StackException $e) {
            // StackException contains the request and response, so rethrow it
            throw $e;

        } catch (Exception $e) {

            // If the exception stores the request and response, rethrow it
            if ($this->hasHttpMessage($e)) {
                throw $e;
            }

            // Capture current state and throw a StackException
            throw new StackException($request, $response, $e);
        }
    }

    /**
    * Resolves a queue entry
    *
    * @param mixed $entry A callable or entry to be resolved
    * @return callable
    *
    * @throws RuntimeException
    */
    protected function resolve($entry)
    {
        // Return the entry if it is already callable
        if (is_callable($entry)) {
            return $entry;
        }

        $callable = $entry;

        if ($this->resolvers) {
            // A resolver will return null if it cannot resolve an entry
            foreach ($this->resolvers as $resolver) {
                $callable = call_user_func($resolver, $entry);
                if ($callable !== null) {
                    break;
                }
            }
        }

        if (is_callable($callable)) {
            return $callable;
        }

        // Unable to resolve the entry
        throw new RuntimeException(self::ERR_RESOLVER);
    }

    /**
    * Checks if the exception contains accessible Psr\Http\Message objects
    *
    * @param Exception $e The exception to check
    * @return bool
    */
    protected function hasHttpMessage(Exception $e)
    {
        if (!method_exists($e, 'getRequest') ||
            $e->getRequest() instanceof ServerRequestInterface === false) {
            return false;
        }

        if (!method_exists($e, 'getResponse') ||
            $e->getResponse() instanceof ResponseInterface === false) {
            return false;
        }

        return true;
    }
}
