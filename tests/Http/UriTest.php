<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use InvalidArgumentException;
use Slim\Http\Environment;
use Slim\Http\Uri;

class UriTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var resource
     */
    protected $uri;

    public function uriFactory()
    {
        $scheme = 'https';
        $host = 'example.com';
        $port = 443;
        $path = '/foo/bar';
        $query = 'abc=123';
        $fragment = 'section3';
        $user = 'josh';
        $password = 'sekrit';

        return new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);
    }

    /********************************************************************************
     * Scheme
     *******************************************************************************/

    public function testGetScheme()
    {
        $this->assertEquals('https', $this->uriFactory()->getScheme());
    }

    public function testWithScheme()
    {
        $uri = $this->uriFactory()->withScheme('http');

        $this->assertAttributeEquals('http', 'scheme', $uri);
    }

    public function testWithSchemeRemovesSuffix()
    {
        $uri = $this->uriFactory()->withScheme('http://');

        $this->assertAttributeEquals('http', 'scheme', $uri);
    }

    public function testWithSchemeEmpty()
    {
        $uri = $this->uriFactory()->withScheme('');

        $this->assertAttributeEquals('', 'scheme', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri scheme must be one of: "", "https", "http"
     */
    public function testWithSchemeInvalid()
    {
        $this->uriFactory()->withScheme('ftp');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri scheme must be a string
     */
    public function testWithSchemeInvalidType()
    {
        $this->uriFactory()->withScheme([]);
    }

    /********************************************************************************
     * Authority
     *******************************************************************************/

    public function testGetAuthorityWithUsernameAndPassword()
    {
        $this->assertEquals('josh:sekrit@example.com', $this->uriFactory()->getAuthority());
    }

    public function testGetAuthorityWithUsername()
    {
        $scheme = 'https';
        $user = 'josh';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh@example.com', $uri->getAuthority());
    }

    public function testGetAuthority()
    {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('example.com', $uri->getAuthority());
    }

    public function testGetAuthorityWithNonStandardPort()
    {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 400;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('example.com:400', $uri->getAuthority());
    }

    public function testGetUserInfoWithUsernameAndPassword()
    {
        $scheme = 'https';
        $user = 'josh';
        $password = 'sekrit';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
    }

    public function testGetUserInfoWithUsername()
    {
        $scheme = 'https';
        $user = 'josh';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh', $uri->getUserInfo());
    }

    public function testGetUserInfoNone()
    {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testWithUserInfo()
    {
        $uri = $this->uriFactory()->withUserInfo('bob', 'pass');

        $this->assertAttributeEquals('bob', 'user', $uri);
        $this->assertAttributeEquals('pass', 'password', $uri);
    }

    public function testWithUserInfoRemovesPassword()
    {
        $uri = $this->uriFactory()->withUserInfo('bob');

        $this->assertAttributeEquals('bob', 'user', $uri);
        $this->assertAttributeEquals('', 'password', $uri);
    }

    public function testGetHost()
    {
        $this->assertEquals('example.com', $this->uriFactory()->getHost());
    }

    public function testWithHost()
    {
        $uri = $this->uriFactory()->withHost('slimframework.com');

        $this->assertAttributeEquals('slimframework.com', 'host', $uri);
    }

    public function testGetPortWithSchemeAndNonDefaultPort()
    {
        $uri = new Uri('https', 'www.example.com', 4000);

        $this->assertEquals(4000, $uri->getPort());
    }

    public function testGetPortWithSchemeAndDefaultPort()
    {
        $uriHppt = new Uri('http', 'www.example.com', 80);
        $uriHppts = new Uri('https', 'www.example.com', 443);

        $this->assertNull($uriHppt->getPort());
        $this->assertNull($uriHppts->getPort());
    }

    public function testGetPortWithoutSchemeAndPort()
    {
        $uri = new Uri('', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testGetPortWithSchemeWithoutPort()
    {
        $uri = new Uri('http', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testWithPort()
    {
        $uri = $this->uriFactory()->withPort(8000);

        $this->assertAttributeEquals(8000, 'port', $uri);
    }

    public function testWithPortNull()
    {
        $uri = $this->uriFactory()->withPort(null);

        $this->assertAttributeEquals(null, 'port', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithPortInvalidInt()
    {
        $this->uriFactory()->withPort(70000);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithPortInvalidString()
    {
        $this->uriFactory()->withPort('Foo');
    }

    /********************************************************************************
     * Path
     *******************************************************************************/

    public function testGetBasePathNone()
    {
        $this->assertEquals('', $this->uriFactory()->getBasePath());
    }

    public function testWithBasePath()
    {
        $uri = $this->uriFactory()->withBasePath('/base');

        $this->assertAttributeEquals('/base', 'basePath', $uri);
    }

    /**
     * @covers Slim\Http\Uri::withBasePath
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri path must be a string
     */
    public function testWithBasePathInvalidType()
    {
        $this->uriFactory()->withBasePath(['foo']);
    }

    public function testWithBasePathAddsPrefix()
    {
        $uri = $this->uriFactory()->withBasePath('base');

        $this->assertAttributeEquals('/base', 'basePath', $uri);
    }

    public function testWithBasePathIgnoresSlash()
    {
        $uri = $this->uriFactory()->withBasePath('/');

        $this->assertAttributeEquals('', 'basePath', $uri);
    }

    public function testGetPath()
    {
        $this->assertEquals('/foo/bar', $this->uriFactory()->getPath());
    }

    public function testWithPath()
    {
        $uri = $this->uriFactory()->withPath('/new');

        $this->assertAttributeEquals('/new', 'path', $uri);
    }

    public function testWithPathWithoutPrefix()
    {
        $uri = $this->uriFactory()->withPath('new');

        $this->assertAttributeEquals('new', 'path', $uri);
    }

    public function testWithPathEmptyValue()
    {
        $uri = $this->uriFactory()->withPath('');

        $this->assertAttributeEquals('', 'path', $uri);
    }

    public function testWithPathUrlEncodesInput()
    {
        $uri = $this->uriFactory()->withPath('/includes?/new');

        $this->assertAttributeEquals('/includes%3F/new', 'path', $uri);
    }

    public function testWithPathDoesNotDoubleEncodeInput()
    {
        $uri = $this->uriFactory()->withPath('/include%25s/new');

        $this->assertAttributeEquals('/include%25s/new', 'path', $uri);
    }

    /**
     * @covers Slim\Http\Uri::withPath
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri path must be a string
     */
    public function testWithPathInvalidType()
    {
        $this->uriFactory()->withPath(['foo']);
    }

    /********************************************************************************
     * Query
     *******************************************************************************/

    public function testGetQuery()
    {
        $this->assertEquals('abc=123', $this->uriFactory()->getQuery());
    }

    public function testWithQuery()
    {
        $uri = $this->uriFactory()->withQuery('xyz=123');

        $this->assertAttributeEquals('xyz=123', 'query', $uri);
    }

    public function testWithQueryRemovesPrefix()
    {
        $uri = $this->uriFactory()->withQuery('?xyz=123');

        $this->assertAttributeEquals('xyz=123', 'query', $uri);
    }

    public function testWithQueryEmpty()
    {
        $uri = $this->uriFactory()->withQuery('');

        $this->assertAttributeEquals('', 'query', $uri);
    }

    /**
     * @covers Slim\Http\Uri::withQuery
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri query must be a string
     */
    public function testWithQueryInvalidType()
    {
        $this->uriFactory()->withQuery(['foo']);
    }

    /********************************************************************************
     * Fragment
     *******************************************************************************/

    public function testGetFragment()
    {
        $this->assertEquals('section3', $this->uriFactory()->getFragment());
    }

    public function testWithFragment()
    {
        $uri = $this->uriFactory()->withFragment('other-fragment');

        $this->assertAttributeEquals('other-fragment', 'fragment', $uri);
    }

    public function testWithFragmentRemovesPrefix()
    {
        $uri = $this->uriFactory()->withFragment('#other-fragment');

        $this->assertAttributeEquals('other-fragment', 'fragment', $uri);
    }

    public function testWithFragmentEmpty()
    {
        $uri = $this->uriFactory()->withFragment('');

        $this->assertAttributeEquals('', 'fragment', $uri);
    }

    /**
     * @covers Slim\Http\Uri::withFragment
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri fragment must be a string
     */
    public function testWithFragmentInvalidType()
    {
        $this->uriFactory()->withFragment(['foo']);
    }

    /********************************************************************************
     * Helpers
     *******************************************************************************/

    public function testToString()
    {
        $uri = $this->uriFactory();

        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withBasePath('foo/');
        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('/bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        // ensure that a Uri with just a base path correctly converts to a string
        // (This occurs via createFromEnvironment when index.php is in a subdirectory)
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/',
            'HTTP_HOST' => 'example.com',
        ]);
        $uri = Uri::createFromEnvironment($environment);
        $this->assertEquals('http://example.com/foo/', (string) $uri);

    }

    /**
     * @covers Slim\Http\Uri::createFromString
     */
    public function testCreateFromString()
    {
        $uri = Uri::createFromString('https://example.com:8080/foo/bar?abc=123');

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
    }

    /**
     * @covers Slim\Http\Uri::createFromString
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri must be a string
     */
    public function testCreateFromStringWithInvalidType()
    {
        Uri::createFromString(['https://example.com:8080/foo/bar?abc=123']);
    }

    public function testCreateEnvironment()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080,
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateEnvironmentWithForwardedProto()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'HTTPS' => '',
            'HTTP_X_FORWARDED_PROTO' => 'https'
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('https', $uri->getScheme());
    }

    public function testCreateEnvironmentWithForwardedHost()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080,
            'HTTP_X_FORWARDED_HOST' => 'example3.com, example2.com, example1.com'
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('example3.com', $uri->getHost());
    }

    /**
     * @covers Slim\Http\Uri::createFromEnvironment
     * @ticket 1375
     */
    public function testCreateEnvironmentWithBasePath()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('/foo', $uri->getBasePath());
        $this->assertEquals('bar', $uri->getPath());

        $this->assertEquals('http://localhost/foo/bar', (string) $uri);
    }

    public function testGetBaseUrl()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:80',
            'SERVER_PORT' => 80
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://example.com/foo', $uri->getBaseUrl());
    }

    public function testGetBaseUrlWithNoBasePath()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:80',
            'SERVER_PORT' => 80
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://example.com', $uri->getBaseUrl());
    }

    public function testGetBaseUrlWithAuthority()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://josh:sekrit@example.com:8080/foo', $uri->getBaseUrl());
    }

    /**
     * @covers Slim\Http\Uri::createFromEnvironment
     * @ticket 1380
     */
    public function testWithPathWhenBaseRootIsEmpty()
    {
        $environment = \Slim\Http\Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/bar',
        ]);
        $uri = \Slim\Http\Uri::createFromEnvironment($environment);

        $this->assertEquals('http://localhost/test', (string) $uri->withPath('test'));
    }
}
