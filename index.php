<?php
// Redirige todo el tráfico a public/index.php
// Solo necesario cuando el document root apunta a la raíz del proyecto en vez de /public
chdir(__DIR__ . '/public');
require __DIR__ . '/public/index.php';
