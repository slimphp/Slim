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
     * Encryption key (should be correct length for selectec algorithm)
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
     * @param  string $data Unencrypted string
     * @param  string $iv   Initialization vector
     * @return string       Encrypted data
     */
    public function encrypt($data)
    {
        if ($data === '' || !extension_loaded('mcrypt')) {
            return $data;
        }

        // Get module
        $module = mcrypt_module_open($this->cipher, '', $this->mode, '');

        // Create IV
        $ivSize = mcrypt_enc_get_iv_size($module);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);

        // Validate key
        $key = $this->key;
        $keySize = mcrypt_enc_get_key_size($module);
        if (strlen($key) > $keySize) {
            $key = substr($key, 0, $keySize);
        }

        // Encrypt value
        mcrypt_generic_init($module, $key, $iv);
        $encryptedData = @mcrypt_generic($module, $data);
        mcrypt_generic_deinit($module);

        // Generate HMAC
        $hmac = $this->getHmac($data);

        return implode('|', array(base64_encode($iv), base64_encode($encryptedData), $hmac));
    }

    /**
     * Decrypt data
     * @param  string $data Encrypted string
     * @param  string $iv   Initialization vector
     * @return string       Decrypted data
     */
    public function decrypt($data)
    {
        if ($data === '' || !extension_loaded('mcrypt')) {
            return $data;
        }

        // Extract components of encrypted data string
        $parts = explode('|', $data);
        $iv = base64_decode($parts[0]);
        $encryptedData = base64_decode($parts[1]);
        $hmac = $parts[2];

        // Get module
        $module = mcrypt_module_open($this->cipher, '', $this->mode, '');

        // Validate IV
        $ivSize = mcrypt_enc_get_iv_size($module);
        if (strlen($iv) > $ivSize) {
            $iv = substr($iv, 0, $ivSize);
        }

        // Validate key
        $key = $this->key;
        $keySize = mcrypt_enc_get_key_size($module);
        if (strlen($key) > $keySize) {
            $key = substr($key, 0, $keySize);
        }

        // Decrypt value
        mcrypt_generic_init($module, $key, $iv);
        $decryptedData = @mdecrypt_generic($module, $encryptedData);
        $decryptedData = str_replace("\x0", '', $decryptedData);
        mcrypt_generic_deinit($module);

        // Verify hmac
        return ($this->getHmac($decryptedData) === $hmac) ? $decryptedData : false;
    }

    /**
     * Generate HMAC message authentication hash to verify and authenticate message integrity
     * @param  string $data Unencrypted data
     * @return string       HMAC hash
     */
    protected function getHmac($data)
    {
        return hash_hmac('sha1', $data, $this->key);
    }
}
