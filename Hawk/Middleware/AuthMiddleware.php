<?php
namespace Hawk\Middleware;

use Exception;
use DomainException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Hawk\Controller\UsersController;
use Hawk\Controller\TokensController;
use Hawk\Exception\UnauthorizedException;

/**
 *
 */
class AuthenticationMiddleware
{
    /**
     * [__invoke description]
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  callable               $next     [description]
     * @return [type]                           [description]
     */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
	{
        $route = $request->getAttribute('route');

		if ($route !== null)
		{
			if ($route->isAuthenticated())
            {
                $accessToken = $route->getArgument('Access-Token');

                // I have to do this, since when an access token is invalid because its third part was tampered with
                // JWT passes the result of the function bellow to hash_equals and generates a php warning saying we
                // passed a boolean and a string was expected. Facepalm firebase/php-jwt.
                if (JWT::urlsafeB64Decode(explode('.', $accessToken)[2]) === false)
                    throw new UnauthorizedException();

                try {
                    $token = JWT::decode($accessToken, TokensController::JWT_SECRET_KEY, [TokensController::JWT_ALGORITHM]);
                }
                catch (BeforeValidException $e) {
                    throw new UnauthorizedException();
                }
                catch (ExpiredException $e) {
                    throw new UnauthorizedException();
                }
                catch (SignatureInvalidException $e) {
                    throw new UnauthorizedException();
                }
                catch (DomainException $e) {
                    throw new UnauthorizedException(); // Invalid json data.
                } catch (Exception $e) {
                    throw new UnauthorizedException();
                }
            }
		}

		return $next($request, $response);
	}
}