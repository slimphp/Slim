<?php
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        return ['Authorization' => 'electrolytes'];
    }
}
