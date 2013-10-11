<?php

namespace Slim\Interfaces;

interface SessionInterface
{
    public function start();

    public function save();

    public function encrypt(CryptInterface $crypt);

    public function decrypt(CryptInterface $crypt);
}
