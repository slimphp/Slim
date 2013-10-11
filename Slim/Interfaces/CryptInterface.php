<?php

namespace Slim\Interfaces;

interface CryptInterface
{
    public function encrypt($data);

    public function decrypt($data);
}
