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
 * @package    Slim
 * @author     Josh Lockhart
 * @since      2.3.0
 */
class Crypt
{
    /**
     * Encryption key (should be correct length for selected cipher)
     * @var string
     */
    protected $key;

    /**
     * Encryption cipher
     * @var integer
     * @see http://www.php.net/manual/mcrypt.ciphers.php
     */
    protected $cipher;

    /**
     * Encryption mode
     * @var integer
     * @see http://www.php.net/manual/mcrypt.constants.php
     */
    protected $mode;

    /**
     * Constructor
     * @param string  $key       Encryption key
     * @param int     $cipher    Encryption algorithm
     * @param integer $mode      Encryption mode
     */
    public function __construct($key, $cipher = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC)
    {
        $this->key = $key;
        $this->cipher = $cipher;
        $this->mode = $mode;
    }

    /**
     * Encrypt data
     * @param  string               $data        Unencrypted data
     * @return string                            Encrypted data
     * @throws \RuntimeException                 If mcrypt extension not loaded
     * @throws \RuntimeException                 If encryption module initialization failed
     */
    public function encrypt($data)
    {
        if (extension_loaded('mcrypt') === false) {
            throw new \RuntimeException('The PHP mcrypt extension must be installed to use encryption');
        }

        // Get module
        $module = mcrypt_module_open($this->cipher, '', $this->mode, '');

        // Create initialization vector
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($module), MCRYPT_DEV_URANDOM);

        // Validate key length
        $this->validateKey($this->key, $module);

        // Initialize encryption
        $initResult = mcrypt_generic_init($module, $this->key, $iv);
        if (!is_null($initResult)) {
            switch ($initResult) {
                case -4:
                    throw new \RuntimeException('There was a memory allocation problem while calling \Slim\Crypt::decrypt');
                    break;
                case -3:
                    throw new \RuntimeException('An incorrect encryption key length was used while calling \Slim\Crypt::decrypt');
                    break;
                default:
                    if (is_integer($initResult) && $initResult < 0) {
                        throw new \RuntimeException('An unknown error was caught while calling \Slim\Crypt::decrypt');
                    }
                    break;
            }
        }

        // Encrypt
        $encryptedData = mcrypt_generic($module, $data);

        // Deinitialize encryption
        mcrypt_generic_deinit($module);

        // Ensure integrity of encrypted data with HMAC hash
        $hmac = $this->getHmac($encryptedData);

        return implode('|', array(base64_encode($iv), base64_encode($encryptedData), $hmac));
    }

    /**
     * Decrypt data
     * @param  string $data Encrypted string
     * @return string       Decrypted data
     * @throws \RuntimeException                 If mcrypt extension not loaded
     * @throws \RuntimeException                 If encrypted data does not match expected format
     * @throws \RuntimeException                 If decryption module initialization failed
     * @throws \RuntimeException                 If HMAC integrity verification fails
     */
    public function decrypt($data)
    {
        if (extension_loaded('mcrypt') === false) {
            throw new \RuntimeException('The PHP mcrypt extension must be installed to use encryption');
        }

        // Extract components of encrypted data string
        $parts = explode('|', $data);
        if (count($parts) !== 3) {
            return $data;
            // throw new \RuntimeException('Trying to decrypt invalid data in \Slim\Crypt::decrypt');
        }
        $iv = base64_decode($parts[0]);
        $encryptedData = base64_decode($parts[1]);
        $hmac = $parts[2];

        // Verify integrity of encrypted data
        if ($this->getHmac($encryptedData) !== $hmac) {
            throw new \RuntimeException('Integrity of encrypted data has been compromised in \Slim\Crypt::decrypt');
        }

        // Get module
        $module = mcrypt_module_open($this->cipher, '', $this->mode, '');

        // Validate key
        $this->validateKey($this->key, $module);

        // Initialize decryption
        $initResult = mcrypt_generic_init($module, $this->key, $iv);
        if (!is_null($initResult)) {
            switch ($initResult) {
                case -4:
                    throw new \RuntimeException('There was a memory allocation problem while calling \Slim\Crypt::decrypt');
                    break;
                case -3:
                    throw new \RuntimeException('An incorrect encryption key length was used while calling \Slim\Crypt::decrypt');
                    break;
                default:
                    if (is_integer($initResult) && $initResult < 0) {
                        throw new \RuntimeException('An unknown error was caught while calling \Slim\Crypt::decrypt');
                    }
                    break;
            }
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
     * Get key
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Validate encryption key based on valid key sizes for selected cipher and cipher mode
     * @param  string   $key    Encryption key
     * @param  resource $module Encryption module
     * @return void
     * @throws \InvalidArgumentException If key size is invalid for selected cipher
     */
    protected function validateKey($key, $module)
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
}
