<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Http;

use Slim\Interfaces\Http\TrustedProxiesInterface;

/**
 * TrustedProxies
 *
 * This class is used to store trusted proxy data for XFF headers.
 *
 * @package Slim\Http
 * @author Michael Yoo <michael@yoo.id.au>
 * @since 3.0.0
 */
class TrustedProxies implements TrustedProxiesInterface
{
    const HEADER_CLIENT_PROTO = 0;
    const HEADER_CLIENT_IP = 1;
    const HEADER_CLIENT_PORT = 2;

    /**
     * Array of addresses (with or without CIDR) of trusted reverse proxies.
     *
     * @var array
     */
    protected $trustedProxies;

    /**
     * Array of the trusted reverse proxy's header names that will be used to recover the original request.
     *
     * @var array
     */
    protected $trustedHeaderNames = array(
        self::HEADER_CLIENT_PROTO => "X-Forwarded-Proto",
        self::HEADER_CLIENT_IP => "X-Forwarded-For",
        self::HEADER_CLIENT_PORT => "X-Forwarded-Port"
    );

    /**
     * Create a new TrustedProxies object
     *
     * @param array $trustedProxies
     * @param array $trustedHeaderNames
     */
    private function __construct($trustedProxies = array(), $trustedHeaderNames = array())
    {
        $this->trustedProxies = $trustedProxies;
        $this->trustedHeaderNames = $trustedHeaderNames + $this->trustedHeaderNames;
    }

    /**
     * Get an array of trusted proxies
     *
     * @return array
     */
    public function getTrustedProxies()
    {
        return $this->trustedProxies;
    }

    public function getTrustedHeaderName($key)
    {
        if(isset($this->trustedHeaderNames[$key]))
        {
            return $this->trustedHeaderNames[$key];
        }

        throw new \LogicException("Trusted Header Name $key is undefined");
    }

    /**
     * Get an array of trusted header names
     *
     * @return array
     */
    public function getTrustedHeaderNames()
    {
        return $this->trustedHeaderNames;
    }

    public function check($address)
    {
        return static::checkIp($address, $this->trustedProxies);
    }

    public static function create($trustedProxies = array(), $trustedHeaderNames = array())
    {
        return static::__set_state($trustedProxies, $trustedHeaderNames);
    }

    public static function __set_state($trustedProxies = array(), $trustedHeaderNames = array())
    {
        return new static($trustedProxies, $trustedHeaderNames);
    }

    /*****************************************************************************
     * Below portion of source code was taken from Symfony/HttpFoundation
     * https://github.com/symfony/HttpFoundation/blob/master/IpUtils.php
     * Under the MIT License.
     *****************************************************************************/

    /**
     * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
     *
     * @param string       $requestIp IP to check
     * @param string|array $ips       List of IPs or subnets (can be a string if only a single one)
     *
     * @return bool Whether the IP is valid
     */
    protected static function checkIp($requestIp, $ips)
    {
        if (!is_array($ips)) {
            $ips = array($ips);
        }

        $method = substr_count($requestIp, ':') > 1 ? 'checkIp6' : 'checkIp4';

        foreach ($ips as $ip) {
            if (self::$method($requestIp, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compares two IPv4 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @param string $requestIp IPv4 address to check
     * @param string $ip        IPv4 address or subnet in CIDR notation
     *
     * @return bool Whether the IP is valid
     */
    protected static function checkIp4($requestIp, $ip)
    {
        if (false !== strpos($ip, '/')) {
            list($address, $netmask) = explode('/', $ip, 2);

            if ($netmask < 1 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 32;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }

    /**
     * Compares two IPv6 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @author David Soria Parra <dsp at php dot net>
     *
     * @see https://github.com/dsp/v6tools
     *
     * @param string $requestIp IPv6 address to check
     * @param string $ip        IPv6 address or subnet in CIDR notation
     *
     * @return bool Whether the IP is valid
     *
     * @throws \RuntimeException When IPV6 support is not enabled
     */
    protected static function checkIp6($requestIp, $ip)
    {
        if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
            throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
        }

        if (false !== strpos($ip, '/')) {
            list($address, $netmask) = explode('/', $ip, 2);

            if ($netmask < 1 || $netmask > 128) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 128;
        }

        $bytesAddr = unpack("n*", inet_pton($address));
        $bytesTest = unpack("n*", inet_pton($requestIp));

        for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; $i++) {
            $left = $netmask - 16 * ($i-1);
            $left = ($left <= 16) ? $left : 16;
            $mask = ~(0xffff >> $left) & 0xffff;
            if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
                return false;
            }
        }

        return true;
    }
}
