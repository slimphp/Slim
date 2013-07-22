<?php
namespace Slim\Helper;

class EncryptedSet extends \Slim\Helper\Set
{
    /**
     * Is encryption enabled for this set?
     * @var boolean
     */
    protected $encrypt = false;

    /**
     * Enable encryption for this set
     * @return void
     */
    public function enableEncryption()
    {
        $this->encrypt = true;
    }

    /**
     * Disable encryption for this set
     * @return void
     */
    public function disableEncryption()
    {
        $this->encrypt = false;
    }

    /**
     * Is this set encrypted?
     * @return boolean
     */
    public function isEncrypted()
    {
        return $this->encrypt;
    }

    /**
     * Encrypt set
     * @param  \Slim\Crypt $crypt
     * @return void
     */
    public function encrypt(\Slim\Crypt $crypt)
    {
        foreach ($this as $elementName => $elementValue) {
            $this->set($elementName, $crypt->encrypt($elementValue));
        }
    }

    /**
     * Decrypt set
     * @param  \Slim\Crypt $crypt
     * @return void
     */
    public function decrypt(\Slim\Crypt $crypt)
    {
        foreach ($this as $elementName => $elementValue) {
            $this->set($elementName, $crypt->decrypt($elementValue));
        }
    }
}
