<?php
namespace Slim\Http;

/**
 * Value object representing a URI for use in HTTP requests.
 *
 * This interface is meant to represent only URIs for use with HTTP requests,
 * and is not intended as a general-purpose URI implementation.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return a new instance that contains the
 * changed state.
 *
 * Typically the Host header will be also be present in the request message.
 * For server-side requests, the scheme will typically be discoverable in the
 * server parameters.
 *
 * @link http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
class Uri implements Psr\Http\Message\UriInterface
{
    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $query;

    /**
     * Constructor
     *
     * @param string $scheme
     * @param string $user
     * @param string $password
     * @param string $host
     * @param string $port
     * @param string $path
     * @param string $query
     */
    public function __construct($scheme, $user, $password, $host, $port, $path, $query = '')
    {
        $this->scheme = $scheme;
        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
    }

    /**
     * Create from environment data
     *
     * This method returns a new Uri instance based on data
     * from the application's environment.
     *
     * @param  \Slim\Environment $env
     * @return self
     */
    public function createFromEnvironment(\Slim\Environment $env)
    {
        // Scheme
        if ($env->has('HTTP_X_FORWARDED_PROTO') === true) {
            $scheme = $env->get('HTTP_X_FORWARDED_PROTO');
        } else {
            $https = $env->get('HTTPS', '');
            $scheme = empty($https) || $https === 'off' ? 'http' : 'https';
        }

        // Authority
        $user = $env->get('PHP_AUTH_USER', '');
        $password = $env->get('PHP_AUTH_PW', '');
        if ($env->has('HTTP_X_FORWARDED_HOST') === true) {
            $host = $env->get('HTTP_X_FORWARDED_HOST');
        } else if ($env->has('HTTP_HOST') === true) {
            $host = $env->get('HTTP_HOST');
        } else {
            $host = $env->get('SERVER_NAME');
        }
        $port = $env->get('SERVER_PORT');

        // Path
        $basePath = parse_url($env->get('SCRIPT_NAME'), PHP_URL_PATH);
        $basePathDir = rtrim(dirname($basePath), '/');
        $path = parse_url($env->get('REQUEST_URI'), PHP_URL_PATH);
        if (strpos($path, $basePath) === 0) {
            $theBasePath = $basePath;
            $virtualPath = substr($path, strlen($basePath));
        } else if (strpos($path, $basePathDir) === 0) {
            $theBasePath = $basePathDir;
            $virtualPath = substr($path, strlen($basePathDir));
        } else {
            $theBasePath = '';
            $virtualPath = $path;
        }
        $virtualPath = '/' . ltrim($virtualPath, '/');

        // Query string
        $queryString = $env->get('QUERY_STRING', '');

        // Build Uri
        $uri = new static($scheme, $user, $password, $host, $port, $virtualPath, $queryString);

        return $uri->withBasePath($theBasePath);
    }

    /**
     * Retrieve the URI scheme.
     *
     * Implementations SHOULD restrict values to "http", "https", or an empty
     * string but MAY accommodate other schemes if required.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The string returned MUST omit the trailing "://" delimiter if present.
     *
     * @return string The scheme of the URI.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority portion of the URI.
     *
     * The authority portion of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * This method MUST return an empty string if no authority information is
     * present.
     *
     * @return string Authority portion of the URI, in "[user-info@]host[:port]"
     *     format.
     */
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();
        $showPort = (($scheme === 'https' && (int)$port !== 443) || ($scheme === 'http' && (int)$port !== 80));

        return ($userInfo ? $userInfo . '@' : '') . $host . ($port && $showPort ? ':' . $port : '');
    }

    /**
     * Retrieve the user information portion of the URI, if present.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * Implementations MUST NOT return the "@" suffix when returning this value.
     *
     * @return string User information portion of the URI, if present, in
     *     "username[:password]" format.
     */
    public function getUserInfo()
    {
        if ($this->username && $this->password) {
            $info = $this->username . ':' . $this->password;
        } else {
            $info = $this->username;
        }

        return $info;
    }

    /**
     * Retrieve the host segment of the URI.
     *
     * This method MUST return a string; if no host segment is present, an
     * empty string MUST be returned.
     *
     * @return string Host segment of the URI.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port segment of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The port for the URI.
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Retrieve the base path segment of the URI.
     *
     * This method MUST return a string; if no path is present it MUST return
     * an empty string.
     *
     * @return string The base path segment of the URI.
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Retrieve the path segment of the URI.
     *
     * This method MUST return a string; if no path is present it MUST return
     * an empty string.
     *
     * @return string The path segment of the URI.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * This method MUST return a string; if no query string is present, it MUST
     * return an empty string.
     *
     * The string returned MUST omit the leading "?" character.
     *
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment segment of the URI.
     *
     * This method MUST return a string; if no fragment is present, it MUST
     * return an empty string.
     *
     * The string returned MUST omit the leading "#" character.
     *
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return '';
    }

    /**
     * Create a new instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified scheme. If the scheme
     * provided includes the "://" delimiter, it MUST be removed.
     *
     * Implementations SHOULD restrict values to "http", "https", or an empty
     * string but MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $clone = clone $this;
        $clone->scheme = str_replace('://', '', $scheme);

        return $clone;
    }

    /**
     * Create a new instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user User name to use for authority.
     * @param null|string $password Password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $clone = clone $this;
        $clone->username = $user;
        $clone->password = $password ? $password : '';

        return $clone;
    }

    /**
     * Create a new instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host Hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Create a new instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port Port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        if (is_integer($port) && $port > 65535) {
            throw new \InvalidArgumentException('Invalid port number');
        }
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Set base path
     *
     * @param  string $basePath
     * @return self
     */
    public function withBasePath($basePath)
    {
        $clone = clone $this;
        $clone->basePath = $basePath;

        return $clone;
    }

    /**
     * Create a new instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified path.
     *
     * The path MUST be prefixed with "/"; if not, the implementation MAY
     * provide the prefix itself.
     *
     * An empty path value is equivalent to removing the path.
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        $clone = clone $this;
        $clone->path = '/' . ltrim($path, '/');

        return $clone;
    }

    /**
     * Create a new instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified query string.
     *
     * If the query string is prefixed by "?", that character MUST be removed.
     * Additionally, the query string SHOULD be parseable by parse_str() in
     * order to be valid.
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        $clone = clone $this;
        $clone->query = ltrim($query, '?');

        return $clone;
    }

    /**
     * Create a new instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified URI fragment.
     *
     * If the fragment is prefixed by "#", that character MUST be removed.
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The URI fragment to use with the new instance.
     * @return self A new instance with the specified URI fragment.
     */
    public function withFragment($fragment)
    {
        return $this;
    }

    /**
     * Return the string representation of the URI.
     *
     * Concatenates the various segments of the URI, using the appropriate
     * delimiters:
     *
     * - If a scheme is present, "://" MUST append the value.
     * - If the authority information is present, that value will be
     *   concatenated.
     * - If a path is present, it MUST be prefixed by a "/" character.
     * - If a query string is present, it MUST be prefixed by a "?" character.
     * - If a URI fragment is present, it MUST be prefixed by a "#" character.
     *
     * @return string
     */
    public function __toString()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $basePath = $this->getBasePath();
        $path = $this->getPath();
        $query = $this->getQuery();

        return ($scheme ? $scheme . '://' : '') . $authority . $basePath . $path . ($query ? '?' . $query : '');
    }
}
