<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.0
 * @package     Slim
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Slim;

/**
 * Crypt
 *
 * This class enables secure Slim application data encryption and decryption.
 * Specifically, it is used to encrypt session and HTTP cookie data.
 *
 * This uses the PHP `mcrypt` cryptography library with the MCRYPT_RIJNDAEL_256
 * cipher in cipher block chaining mode. This also uses a random initialization
 * vector with entropy derived from `/dev/urandom`. A unique initialzation vector
 * is created each time you invoke the `encrypt` method. Encrypted data
 * is signed using a hashed message authentication code (HMAC) to verify
 * the data integrity and authenticity during decryption.
 *
 * Even though this class is used by the Slim application behind the scenes,
 * you may also use this class to encrypt your own arbitrary application data.
 * Just invoke `$app->crypt->encrypt()` and `$app->crypt->decrypt($data)`.
 *
 * @package    Slim
 * @author     Josh Lockhart
 * @since      2.3.0
 */
class Crypt implements \Slim\Interfaces\CryptInterface
{
    /**
     * Encryption key (should be correct length for selected cipher)
     * @var string
     */
    protected $key;

    /**
     * Encryption cipher
     * @var int
     * @see http://www.php.net/manual/mcrypt.ciphers.php
     */
    protected $cipher;

    /**
     * Encryption mode
     * @var int
     * @see http://www.php.net/manual/mcrypt.constants.php
     */
    protected $mode;

    /**
     * Constructor
     * @param  string $key    Encryption key
     * @param  int    $cipher Encryption algorithm
     * @param  int    $mode   Encryption mode
     * @api
     */
    public function __construct($key, $cipher = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC)
    {
        $this->checkRequirements();

        $this->key = $key;
        $this->cipher = $cipher;
        $this->mode = $mode;
    }

    /**
     * Encrypt data
     * @param  string            $data Unencrypted data
     * @return string                  Encrypted data
     * @throws \RuntimeException       If mcrypt extension not loaded
     * @throws \RuntimeException       If encryption module initialization failed
     * @api
     */
    public function encrypt($data)
    {
        // Get module
        $module = mcrypt_module_open($this->cipher, '', $this->mode, '');

        // Create initialization vector
        $vector = mcrypt_create_iv(mcrypt_enc_get_iv_size($module), MCRYPT_DEV_URANDOM);

        // Validate key length
        $this->validateKeyLength($this->key, $module);

        // Initialize encryption
        $initResult = mcrypt_generic_init($module, $this->key, $vector);

        if (!is_null($initResult)) {
            $this->throwInitError($initResult, 'encrypt');
        }

        // Encrypt
        $encryptedData = mcrypt_generic($module, $data);

        // Deinitialize encryption
        mcrypt_generic_deinit($module);

        // Ensure integrity of encrypted data with HMAC hash
        $hmac = $this->getHmac($encryptedData);

        return implode('|', array(base64_encode($vector), base64_encode($encryptedData), $hmac));
    }

    /**
     * Decrypt data
     * @param  string            $data Encrypted string
     * @return string                  Decrypted data
     * @throws \RuntimeException       If mcrypt extension not loaded
     * @throws \RuntimeException       If decryption module initialization failed
     * @throws \RuntimeException       If HMAC integrity verification fails
     * @api
     */
    public function decrypt($data)
    {
        // Extract components of encrypted data string
        $parts = explode('|', $data);
        if (count($parts) !== 3) {
            return $data;
            // throw new \RuntimeException('Trying to decrypt invalid data in \Slim\Crypt::decrypt');
        }
        $vector = base64_decode($parts[0]);
        $encryptedData = base64_decode($parts[1]);
        $hmac = $parts[2];

        // Verify integrity of encrypted data
        if ($this->getHmac($encryptedData) !== $hmac) {
            throw new \RuntimeException('Integrity of encrypted data has been compromised in \Slim\Crypt::decrypt');
        }

        // Get module
        $module = mcrypt_module_open($this->cipher, '', $this->mode, '');

        // Validate key
        $this->validateKeyLength($this->key, $module);

        // Initialize decryption
        $initResult = mcrypt_generic_init($module, $this->key, $vector);

        if (!is_null($initResult)) {
            $this->throwInitError($initResult, 'decrypt');
        }

        // Decrypt
        $decryptedData = mdecrypt_generic($module, $encryptedData);
        $decryptedData = str_replace("\x0", '', $decryptedData);

        // Deinitialize decryption
        mcrypt_generic_deinit($module);

        return $decryptedData;
    }

    /**
     * Generate HMAC message authentication hash to verify and authenticate message integrity
     * @param  string $data Unencrypted data
     * @return string       HMAC hash
     */
    protected function getHmac($data)
    {
        return hash_hmac('sha256', $data, $this->key);
    }

    /**
     * Validate encryption key based on valid key sizes for selected cipher and cipher mode
     * @param  string                    $key    Encryption key
     * @param  resource                  $module Encryption module
     * @return void
     * @throws \InvalidArgumentException         If key size is invalid for selected cipher
     */
    protected function validateKeyLength($key, $module)
    {
        $keySize = strlen($key);
        $keySizeMin = 1;
        $keySizeMax = mcrypt_enc_get_key_size($module);
        $validKeySizes = mcrypt_enc_get_supported_key_sizes($module);
        if ($validKeySizes) {
            if (!in_array($keySize, $validKeySizes)) {
                throw new \InvalidArgumentException('Encryption key length must be one of: ' . implode(', ', $validKeySizes));
            }
        } else {
            if ($keySize < $keySizeMin || $keySize > $keySizeMax) {
                throw new \InvalidArgumentException(sprintf(
                    'Encryption key length must be between %s and %s, inclusive',
                    $keySizeMin,
                    $keySizeMax
                ));
            }
        }
    }

    /**
     * Check the mcrypt PHP extension is loaded
     * @throws \RuntimeException If the mcrypt PHP extension is missing
     */
    protected function checkRequirements()
    {
        if (extension_loaded('mcrypt') === false) {
            throw new \RuntimeException(sprintf(
                'The PHP mcrypt extension must be installed to use the %s encryption class.',
                __CLASS__
            ));
        }
    }

    /**
     * Throw an exception based on a provided exit code
     * @param  mixed  $code
     * @param  string $function
     * @throws \RuntimeException If there was a memory allocation problem
     * @throws \RuntimeException If there was an incorrect key length specified
     * @throws \RuntimeException If an unknown error occured
     */
    protected function throwInitError($code, $function)
    {
        switch ($code) {
            case -4:
                throw new \RuntimeException(sprintf(
                    'There was a memory allocation problem while calling %s::%s',
                    __CLASS__,
                    $function
                ));
                break;
            case -3:
                throw new \RuntimeException(sprintf(
                    'An incorrect encryption key length was used while calling %s::%s',
                    __CLASS__,
                    $function
                ));
                break;
            default:
                if (is_integer($code) && $code < 0) {
                    throw new \RuntimeException(sprintf(
                        'An unknown error was caught while calling %s::%s',
                        __CLASS__,
                        $function
                    ));
                }
                break;
        }
    }
}
