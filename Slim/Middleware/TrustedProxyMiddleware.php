<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Rewrites the request so it reflects the real client when the application
 * runs behind a trusted reverse proxy (nginx, Apache, AWS ALB, Cloudflare,
 * a Kubernetes ingress, etc.).
 *
 * Honors the RFC 7239 `Forwarded` header first, then falls back to the
 * de-facto `X-Forwarded-For`, `X-Forwarded-Proto`, `X-Forwarded-Host` and
 * `X-Forwarded-Port` headers. Forwarded headers are consulted only when the
 * immediate TCP peer (`REMOTE_ADDR`) is part of the configured trusted-proxy
 * whitelist, which prevents client-supplied header spoofing.
 *
 * Effects on the request handed to the next middleware:
 *   - The `client_ip` attribute holds the resolved client address.
 *   - The URI scheme, host and port reflect what the client requested
 *     externally, so absolute URL generation behaves correctly.
 *
 * The default trusted-proxy list is empty: the middleware is a no-op until
 * explicitly configured, which is the only safe default.
 */
final class TrustedProxyMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE_CLIENT_IP = 'client_ip';

    /**
     * @var array<string> Trusted proxy IPs or CIDRs (IPv4 and IPv6).
     */
    private array $trustedProxies = [];

    /**
     * @var array<string> Lower-cased forwarded headers that will be honored.
     */
    private array $trustedHeaders = [
        'forwarded',
        'x-forwarded-for',
        'x-forwarded-proto',
        'x-forwarded-host',
        'x-forwarded-port',
    ];

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $remoteAddr = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');

        if ($remoteAddr === '' || !$this->isTrustedProxy($remoteAddr)) {
            return $handler->handle($request);
        }

        $forwarded = $this->parseForwarded($request);

        $clientIp = $this->resolveClientIp($forwarded, $remoteAddr);
        $request = $request->withAttribute(self::ATTRIBUTE_CLIENT_IP, $clientIp);

        $request = $this->rewriteUri($request, $forwarded);

        return $handler->handle($request);
    }

    /**
     * Set the list of trusted proxy addresses or CIDR ranges (IPv4 / IPv6).
     *
     * Forwarded headers are honored only when `REMOTE_ADDR` matches one of
     * these entries. Pass an empty array to disable the middleware.
     *
     * @param array<string> $proxies
     */
    public function withTrustedProxies(array $proxies): self
    {
        $clone = clone $this;
        $clone->trustedProxies = $proxies;

        return $clone;
    }

    /**
     * Restrict which forwarded headers are honored. Names are matched
     * case-insensitively. Defaults to the RFC 7239 `Forwarded` header plus
     * the four common `X-Forwarded-*` headers.
     *
     * @param array<string> $headers
     */
    public function withTrustedHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->trustedHeaders = array_map('strtolower', $headers);

        return $clone;
    }

    /**
     * Parse the immediate hop from the RFC 7239 `Forwarded` header and the
     * `X-Forwarded-*` fallbacks.
     *
     * @return array{for?:string,proto?:string,host?:string,port?:int}
     */
    private function parseForwarded(ServerRequestInterface $request): array
    {
        $parsed = [];

        if ($this->headerIsTrusted('forwarded') && $request->hasHeader('Forwarded')) {
            // Use only the first forwarded element (closest hop) per RFC 7239.
            $element = explode(',', $request->getHeaderLine('Forwarded'), 2)[0];
            foreach (explode(';', $element) as $pair) {
                if (!str_contains($pair, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', trim($pair), 2);
                $key = strtolower($key);
                $value = trim($value, " \t\"");
                if ($key === 'for' || $key === 'proto' || $key === 'host') {
                    $parsed[$key] = $value;
                }
            }
        }

        if (
            !isset($parsed['for'])
            && $this->headerIsTrusted('x-forwarded-for')
            && $request->hasHeader('X-Forwarded-For')
        ) {
            $parsed['for'] = $request->getHeaderLine('X-Forwarded-For');
        }

        if (
            !isset($parsed['proto'])
            && $this->headerIsTrusted('x-forwarded-proto')
            && $request->hasHeader('X-Forwarded-Proto')
        ) {
            $parsed['proto'] = strtolower(trim($request->getHeaderLine('X-Forwarded-Proto')));
        }

        if (
            !isset($parsed['host'])
            && $this->headerIsTrusted('x-forwarded-host')
            && $request->hasHeader('X-Forwarded-Host')
        ) {
            $parsed['host'] = trim($request->getHeaderLine('X-Forwarded-Host'));
        }

        if ($this->headerIsTrusted('x-forwarded-port') && $request->hasHeader('X-Forwarded-Port')) {
            $port = trim($request->getHeaderLine('X-Forwarded-Port'));
            if (ctype_digit($port)) {
                $portInt = (int)$port;
                if ($portInt > 0 && $portInt <= 65535) {
                    $parsed['port'] = $portInt;
                }
            }
        }

        return $parsed;
    }

    /**
     * Walk the forwarded chain right-to-left, popping addresses while every
     * preceding hop is itself a trusted proxy, and return the first
     * untrusted hop (the real client).
     *
     * @param array{for?:string,proto?:string,host?:string,port?:int} $forwarded
     */
    private function resolveClientIp(array $forwarded, string $remoteAddr): string
    {
        $for = $forwarded['for'] ?? null;
        if ($for === null || $for === '') {
            return $remoteAddr;
        }

        $chain = [];
        foreach (explode(',', $for) as $atom) {
            $ip = $this->normalizeForwardedAddress($atom);
            if ($ip !== null) {
                $chain[] = $ip;
            }
        }

        if ($chain === []) {
            return $remoteAddr;
        }

        for ($i = count($chain) - 1; $i >= 0; $i--) {
            if (!$this->isTrustedProxy($chain[$i])) {
                return $chain[$i];
            }
        }

        // Every entry in the chain was trusted - the originating client is
        // the leftmost address, which is as far back as the chain goes.
        return $chain[0];
    }

    /**
     * @param array{for?:string,proto?:string,host?:string,port?:int} $forwarded
     */
    private function rewriteUri(ServerRequestInterface $request, array $forwarded): ServerRequestInterface
    {
        $uri = $request->getUri();
        $changed = false;

        if (isset($forwarded['proto']) && in_array($forwarded['proto'], ['http', 'https'], true)) {
            if ($uri->getScheme() !== $forwarded['proto']) {
                $uri = $uri->withScheme($forwarded['proto']);
                $changed = true;
            }
        }

        if (isset($forwarded['host']) && $forwarded['host'] !== '') {
            [$hostName, $hostPort] = $this->splitHostPort($forwarded['host']);
            if ($hostName !== '' && $hostName !== $uri->getHost()) {
                $uri = $uri->withHost($hostName);
                $changed = true;
            }
            if ($hostPort !== null && $uri->getPort() !== $hostPort) {
                $uri = $uri->withPort($hostPort);
                $changed = true;
            }
        }

        if (isset($forwarded['port']) && $uri->getPort() !== $forwarded['port']) {
            $uri = $uri->withPort($forwarded['port']);
            $changed = true;
        }

        return $changed ? $request->withUri($uri) : $request;
    }

    private function headerIsTrusted(string $name): bool
    {
        return in_array($name, $this->trustedHeaders, true);
    }

    /**
     * Split a `Host`-style header value into hostname and optional port.
     * Handles bracketed IPv6 (`[::1]:8443`) and IPv4/hostname (`example.com:8443`).
     *
     * @return array{0:string,1:int|null}
     */
    private function splitHostPort(string $value): array
    {
        if (str_starts_with($value, '[')) {
            $end = strpos($value, ']');
            if ($end === false) {
                return ['', null];
            }
            $host = substr($value, 1, $end - 1);
            $rest = substr($value, $end + 1);
            $port = null;
            if (str_starts_with($rest, ':') && ctype_digit(substr($rest, 1))) {
                $port = (int)substr($rest, 1);
            }

            return [$host, $port];
        }

        if (substr_count($value, ':') === 1) {
            [$host, $portPart] = explode(':', $value, 2);
            $port = ctype_digit($portPart) ? (int)$portPart : null;

            return [$host, $port];
        }

        return [$value, null];
    }

    /**
     * Strip RFC 7239 quoting, brackets and trailing port from a forwarded
     * address atom.
     */
    private function normalizeForwardedAddress(string $value): ?string
    {
        $value = trim($value, " \t\"");

        if ($value === '' || strcasecmp($value, 'unknown') === 0) {
            return null;
        }

        if (str_starts_with($value, '[')) {
            $end = strpos($value, ']');
            if ($end === false) {
                return null;
            }

            return substr($value, 1, $end - 1);
        }

        if (substr_count($value, ':') === 1) {
            return explode(':', $value, 2)[0];
        }

        return $value;
    }

    private function isTrustedProxy(string $address): bool
    {
        foreach ($this->trustedProxies as $proxy) {
            if ($this->addressMatches($address, $proxy)) {
                return true;
            }
        }

        return false;
    }

    private function addressMatches(string $address, string $subject): bool
    {
        if (!str_contains($subject, '/')) {
            return $address === $subject;
        }

        [$range, $bits] = explode('/', $subject, 2);
        if (!ctype_digit($bits)) {
            return false;
        }

        $addressPacked = @inet_pton($address);
        $rangePacked = @inet_pton($range);
        if ($addressPacked === false || $rangePacked === false) {
            return false;
        }

        if (strlen($addressPacked) !== strlen($rangePacked)) {
            return false;
        }

        $bits = (int)$bits;
        $maxBits = strlen($addressPacked) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $tailBits = $bits % 8;

        if ($fullBytes > 0 && substr($addressPacked, 0, $fullBytes) !== substr($rangePacked, 0, $fullBytes)) {
            return false;
        }

        if ($tailBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $tailBits)) & 0xFF;

        return (ord($addressPacked[$fullBytes]) & $mask) === (ord($rangePacked[$fullBytes]) & $mask);
    }
}
