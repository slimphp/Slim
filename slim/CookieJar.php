<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
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

/**
 * Cooke Jar
 *
 * Manages secure cookies and provides:
 * - Cookie data integrity and authenticity (with HMAC)
 * - Confidentiality (with symmetric encryption)
 * - Protection from replay attack (if using SSL or TLS)
 * - Protection from interception (if using SSL or TLS)
 *
 * This code was originally called "BigOrNot_CookieManager" and written by
 * Matthieu Huguet released under "CopyLeft" license. I have cleaned up the
 * code formatting to conform with Slim Framework contributor guidelines.
 *
 * @author Matthies Huguet <http://bigornot.blogspot.com/2008/06/security-cookies-and-rest.html>
 */
class CookieJar {

	/* Server secret key */
	protected $_secret = '';

	/* Cryptographic algorithm used to encrypt cookies data */
 	protected $_algorithm = MCRYPT_RIJNDAEL_256;

	/* Cryptographic mode (CBC, CFB ...)*/
	protected $_mode = MCRYPT_MODE_CBC;

	/* mcrypt module resource */
	protected $_cryptModule = null;

	/* Enable high confidentiality for cookie value (symmetric encryption) */
	protected $_highConfidentiality = true;

	/* Enable SSL support */
	protected $_ssl = false;

	/* Cookies */
	protected $_cookies = array();

	/**
	 * Constructor
	 *
	 * Initialize cookie manager and mcrypt module.
	 *
	 * @param string $secret  server's secret key
	 * @param array $config
	 */
	public function __construct($secret, $config = null) {
		if ( empty($secret) ) {
			throw new Exception('You must provide a secret key');
		}
		$this->_secret = $secret;
		if ( $config !== null && !is_array($config) ) {
			throw new Exception('Config must be an array');
		}
		if ( is_array($config) ) {
			if ( isset($config['high_confidentiality']) ) {
				$this->_highConfidentiality = $config['high_confidentiality'];
			}
			if ( isset($config['mcrypt_algorithm']) ) {
				$this->_algorithm = $config['mcrypt_algorithm'];
			}
			if ( isset($config['mcrypt_mode']) ) {
				$this->_mode = $config['mcrypt_mode'];
			}
			if ( isset($config['enable_ssl']) ) {
				$this->_ssl = $config['enable_ssl'];
			}
		}
		$this->_cryptModule = mcrypt_module_open($this->_algorithm, '', $this->_mode, '');
		if ( $this->_cryptModule === false ) {
			throw new Exception('Error while loading mcrypt module');
		}
	}

	/**
	 * Get the high confidentiality mode
	 *
	 * @return bool TRUE if cookie data encryption is enabled, or FALSE if it isn't
	 */
	public function getHighConfidentiality() {
		return $this->_highConfidentiality;
	}

	/**
	 * Set the high confidentiality mode
	 * Enable or disable cookie data encryption
	 *
	 * @param bool $enable  TRUE to enable, FALSE to disable
	 */
	public function setHighConfidentiality($enable) {
		$this->_highConfidentiality = $enable;
		return $this;
	}

	/**
	 * Get the SSL status (enabled or disabled?)
	 *
	 * @return bool TRUE if SSL support is enabled, or FALSE if it isn't
	 */
	public function getSSL() {
		return ($this->_ssl);
	}

	/**
	 * Enable SSL support (not enabled by default)
	 * pro: protect against replay attack
	 * con: cookie's lifetime is limited to SSL session's lifetime
	 *
	 * @param bool $enable TRUE to enable, FALSE to disable
	 */
	public function setSSL($enable) {
		$this->_ssl = $enable;
		return $this;
	}

	/**
	 * Get Response Cookies
	 *
	 * @author Josh Lockhart <info@joshlockhart.com>
	 * @return array Cookies to be sent with HTTP response
	 */
	public function getResponseCookies() {
		return $this->_cookies;
	}

	/**
	 * Get Response Cookie
	 *
	 * @author Josh Lockhart <info@joshlockhart.com>
	 * @param string $cookiename The name of the cookie
	 * @return Cookie|null
	 */
	public function getResponseCookie($cookiename) {
		return isset($this->_cookies[$cookiename]) ? $this->_cookies[$cookiename] : null;
	}

	/**
	 * Send a secure cookie
	 *
	 * @param string $name cookie name
	 * @param string $value cookie value
	 * @param string $username user name (or ID)
	 * @param integer $expire expiration time
	 * @param string $path cookie path
	 * @param string $domain cookie domain
	 * @param bool $secure when TRUE, send the cookie only on a secure connection
	 * @param bool $httponly when TRUE the cookie will be made accessible only through the HTTP protocol
	 */
	public function setCookie($cookiename, $value, $username, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = null) {
		$secureValue = $this->_secureCookieValue($value, $username, $expire);
		$this->setClassicCookie($cookiename, $secureValue, $expire, $path, $domain, $secure, $httponly);
	}

	/**
	 * Delete a cookie
	 *
	 * @param string $name cookie name
	 * @param string $path cookie path
	 * @param string $domain cookie domain
	 * @param bool $secure when TRUE, send the cookie only on a secure connection
	 * @param bool $httponly when TRUE the cookie will be made accessible only through the HTTP protocol
	 */
	public function deleteCookie($name, $path = '/', $domain = '', $secure = false, $httponly = null) {
		$expire = 315554400; /* 1980-01-01 */
		$this->_cookies[$name] = new Cookie($name, '', $path, $domain, $secure, $httponly);
		//setcookie($name, '', $expire, $path, $domain, $secure, $httponly);
	}

	/**
	 * Get a secure cookie value
	 *
	 * Verify the integrity of cookie data and decrypt it.
	 * If the cookie is invalid, it can be automatically destroyed (default behaviour)
	 *
	 * @param string $cookiename cookie name
	 * @param bool $deleteIfInvalid destroy the cookie if invalid
	 */
	public function getCookieValue($cookiename, $deleteIfInvalid = true) {
		if ( $this->cookieExists($cookiename) ) {
			$cookieValues = explode('|', $_COOKIE[$cookiename]);
			if ( (count($cookieValues) === 4) && ($cookieValues[1] == 0 || $cookieValues[1] >= time()) ) {
				$key = hash_hmac('sha1', $cookieValues[0] . $cookieValues[1], $this->_secret);
				$cookieData = base64_decode($cookieValues[2]);
				if ( $this->getHighConfidentiality() ) {
					$data = $this->_decrypt($cookieData, $key, md5($cookieValues[1]));
				} else {
					$data = $cookieData;
				}
				if ( $this->_ssl && isset($_SERVER['SSL_SESSION_ID']) ) {
					$verifKey = hash_hmac('sha1', $cookieValues[0] . $cookieValues[1] . $data . $_SERVER['SSL_SESSION_ID'], $key);
				} else {
					$verifKey = hash_hmac('sha1', $cookieValues[0] . $cookieValues[1] . $data, $key);
				}
				if ( $verifKey == $cookieValues[3] ) {
					return $data;
				}
			}
		}
		if ( $deleteIfInvalid ) {
			$this->deleteCookie($cookiename);
		}
		return (false);
	}

	/**
	 * Send a classic (unsecure) cookie
	 *
	 * @param string $name cookie name
	 * @param string $value cookie value
	 * @param integer $expire expiration time
	 * @param string $path cookie path
	 * @param string $domain cookie domain
	 * @param bool $secure when TRUE, send the cookie only on a secure connection
	 * @param bool $httponly when TRUE the cookie will be made accessible only through the HTTP protocol
	 */
	public function setClassicCookie($cookiename, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = null) {
		/* httponly option is only available for PHP version >= 5.2 */
		if ( $httponly === null ) {
			$this->_cookies[$cookiename] = new Cookie($cookiename, $value, $expire, $path, $domain, $secure);
			//setcookie($cookiename, $value, $expire, $path, $domain, $secure);
		} else {
			$this->_cookies[$cookiename] = new Cookie($cookiename, $value, $expire, $path, $domain, $secure, $httponly);
			//setcookie($cookiename, $value, $expire, $path, $domain, $secure, $httponly);
		}
	}

	/**
	 * Verify if a cookie exists
	 *
	 * @param string $cookiename
	 * @return bool TRUE if cookie exist, or FALSE if not
	 */
	public function cookieExists($cookiename) {
		return isset($_COOKIE[$cookiename]);
	}

	/**
	 * Secure a cookie value
	 *
	 * The initial value is transformed with this protocol :
	 *
	 *  secureValue = username|expire|base64((value)k,expire)|HMAC(user|expire|value,k)
	 *  where k = HMAC(user|expire, sk)
	 *  and sk is server's secret key
	 *  (value)k,md5(expire) is the result an cryptographic function (ex: AES256) on "value" with key k and initialisation vector = md5(expire)
	 *
	 * @param string $value unsecure value
	 * @param string $username user name (or ID)
	 * @param integer $expire expiration time
	 * @return string secured value
	 */
	protected function _secureCookieValue($value, $username, $expire) {
		if ( is_string($expire) ) {
			$expire = strtotime($expire);
		}
		$key = hash_hmac('sha1', $username . $expire, $this->_secret);
		if ( $this->getHighConfidentiality() ) {
			$encryptedValue = base64_encode($this->_encrypt($value, $key, md5($expire)));
		} else {
			$encryptedValue = base64_encode($value);
		}
		if ( $this->_ssl && isset($_SERVER['SSL_SESSION_ID']) ) {
			$verifKey = hash_hmac('sha1', $username . $expire . $value . $_SERVER['SSL_SESSION_ID'], $key);
		} else {
			$verifKey = hash_hmac('sha1', $username . $expire . $value, $key);
		}
		$result = array($username, $expire, $encryptedValue, $verifKey);
		return(implode('|', $result));
	}

	/**
	 * Encrypt a given data with a given key and a given initialisation vector
	 *
	 * @param string $data data to crypt
	 * @param string $key secret key
	 * @param string $iv initialisation vector
	 * @return string encrypted data
	 */
	protected function _encrypt($data, $key, $iv) {
		$iv = $this->_validateIv($iv);
		$key = $this->_validateKey($key);
		mcrypt_generic_init($this->_cryptModule, $key, $iv);
		$res = mcrypt_generic($this->_cryptModule, $data);
		mcrypt_generic_deinit($this->_cryptModule);
		return $res;
	}

	/**
	 * Decrypt a given data with a given key and a given initialisation vector
	 *
	 * @param string $data data to crypt
	 * @param string $key secret key
	 * @param string $iv initialisation vector
	 * @return string encrypted data
	 */
	protected function _decrypt($data, $key, $iv) {
		$iv = $this->_validateIv($iv);
		$key = $this->_validateKey($key);
		mcrypt_generic_init($this->_cryptModule, $key, $iv);
		$decryptedData = mdecrypt_generic($this->_cryptModule, $data);
		$res = str_replace("\x0", '', $decryptedData);
		mcrypt_generic_deinit($this->_cryptModule);
		return $res;
	}

	/**
	 * Validate Initialization vector
	 *
	 * If given IV is too long for the selected mcrypt algorithm, it will be truncated
	 *
	 * @param string $iv Initialization vector
	 */
	protected function _validateIv($iv) {
		$ivSize = mcrypt_enc_get_iv_size($this->_cryptModule);
		if ( strlen($iv) > $ivSize ) {
			$iv = substr($iv, 0, $ivSize);
		}
		return $iv;
	}

	/**
	 * Validate key
	 *
	 * If given key is too long for the selected mcrypt algorithm, it will be truncated
	 *
	 * @param string $key key
	 */
	protected function _validateKey($key) {
		$keySize = mcrypt_enc_get_key_size($this->_cryptModule);
		if ( strlen($key) > $keySize ) {
			$key = substr($key, 0, $keySize);
		}
		return $key;
	}

}

?>