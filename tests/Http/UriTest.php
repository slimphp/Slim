<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
class UriTest extends PHPUnit_Framework_TestCase
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

        return new \Slim\Http\Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);
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
     * @expectedException \InvalidArgumentException
     */
    public function testWithSchemeInvalid()
    {
        $uri = $this->uriFactory()->withScheme('ftp');
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
        $uri = new \Slim\Http\Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

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
        $uri = new \Slim\Http\Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

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
        $uri = new \Slim\Http\Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

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
        $uri = new \Slim\Http\Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

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
        $uri = new \Slim\Http\Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

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
        $uri = new \Slim\Http\Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

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
        $uri = new Slim\Http\Uri('https', 'www.example.com', 4000);

        $this->assertEquals(4000, $uri->getPort());
    }
    
    public function testGetPortWithSchemeAndDefaultPort()
    {
        $uriHppt = new Slim\Http\Uri('http', 'www.example.com', 80);
        $uriHppts = new Slim\Http\Uri('https', 'www.example.com', 443);
        
        $this->assertNull($uriHppt->getPort());
        $this->assertNull($uriHppts->getPort());
    }

    public function testGetPortWithoutSchemeAndPort()
    {
        $uri = new Slim\Http\Uri('', 'www.example.com');
        
        $this->assertNull($uri->getPort());
    }

    public function testGetPortWithSchemeWithoutPort()
    {
        $uri = new Slim\Http\Uri('http', 'www.example.com');
        
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
     * @expectedException \InvalidArgumentException
     */
    public function testWithPortInvalidInt()
    {
        $uri = $this->uriFactory()->withPort(70000);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithPortInvalidString()
    {
        $uri = $this->uriFactory()->withPort('Foo');
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

    public function testWithBasePathAddsPrefix()
    {
        $uri = $this->uriFactory()->withBasePath('base');

        $this->assertAttributeEquals('/base', 'basePath', $uri);
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

    public function testWithPathAddsPrefix()
    {
        $uri = $this->uriFactory()->withPath('new');

        $this->assertAttributeEquals('/new', 'path', $uri);
    }

    public function testWithPathEmptyValue()
    {
        $uri = $this->uriFactory()->withPath('');

        $this->assertAttributeEquals('/', 'path', $uri);
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

    /********************************************************************************
     * Helpers
     *******************************************************************************/

    public function testToString()
    {
        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string)$this->uriFactory());
    }
}
