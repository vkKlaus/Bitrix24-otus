<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

spl_autoload_register(function(string $class): void
{
    if(!str_contains( $class, 'Otus')){
        return;
    }

    $class = str_replace('\\', '/', $class);

    $path = __DIR__ . '/' . $class . '.php';

/*     
    echo '<pre>';
    var_dump ( $path );
    var_dump ( is_file($path) );
    echo '</pre>';
*/

    if(is_file($path)){
        require_once $path;
    }
});

