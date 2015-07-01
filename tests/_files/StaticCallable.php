<?php

class StaticCallable
{
    static public function run($req, $res, $next)
    {
        $res->write('In1');
        $res = $next($req, $res);
        $res->write('Out1');

        return $res;
    }
}