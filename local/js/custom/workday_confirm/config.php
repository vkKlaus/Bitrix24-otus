<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'js' => 'script.js',
    'css' => 'style.css',
    'rel' => ['ui.dialogs.messagebox', 'main.core', 'main.core.events'],
    'skip_core' => false,
];