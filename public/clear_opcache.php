<?php
header('Content-Type: text/plain; charset=UTF-8');

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "¡Éxito! OPcache se ha limpiado correctamente. El servidor cargará la versión más reciente del código.\n";
    } else {
        echo "Error al limpiar OPcache. Es posible que esté desactivado o que requiera privilegios adicionales.\n";
    }
} else {
    echo "La función 'opcache_reset' no existe o está deshabilitada en la configuración de PHP.\n";
}
