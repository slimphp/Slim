<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Tests\Http;

use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\UploadedFile;
use Slim\Http\Uri;

class UploadedFilesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $input The input array to parse.
     * @param array $expected The expected normalized output.
     *
     * @dataProvider providerParseUploadedFiles
     */
    public function testParseUploadedFiles(array $input, array $expected)
    {
        $this->assertEquals($expected, UploadedFile::parseUploadedFiles($input));
    }

    public function providerParseUploadedFiles()
    {
        return [
            [
                [
                    'files' => [
                        'tmp_name' => [
                            0 => __DIR__ . DIRECTORY_SEPARATOR . 'file0.txt',
                            1 => __DIR__ . DIRECTORY_SEPARATOR . 'file1.html',
                        ],
                        'name'     => [
                            0 => 'file0.txt',
                            1 => 'file1.html',
                        ],
                        'type'     => [
                            0 => 'text/plain',
                            1 => 'text/html',
                        ],
                        'error'    => [
                            0 => 0,
                            1 => 0
                        ]
                    ],
                ],
                [
                    'files' => [
                        0 => new UploadedFile(__DIR__ . DIRECTORY_SEPARATOR . 'file0.txt', 'file0.txt', 'text/plain',
                            null, UPLOAD_ERR_OK, true),
                        1 => new UploadedFile(__DIR__ . DIRECTORY_SEPARATOR . 'file1.html', 'file1.html', 'text/html',
                            null, UPLOAD_ERR_OK, true),
                    ],
                ]
            ],
            [
                [
                    'avatar' => [
                        'tmp_name' => 'phpUxcOty',
                        'name'     => 'my-avatar.png',
                        'size'     => 90996,
                        'type'     => 'image/png',
                        'error'    => 0,
                    ],
                ],
                [
                    'avatar' => new UploadedFile('phpUxcOty', 'my-avatar.png', 'image/png', 90996, UPLOAD_ERR_OK, true)
                ]
            ]
        ];
    }

    /**
     * @param array $mockEnv An array representing a mock environment.
     *
     * @return Request
     */
    public function requestFactory(array $mockEnv)
    {
        $env = Environment::mock();

        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $uploadedFiles = UploadedFile::createFromEnvironment($env);
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        return $request;
    }

    
}
