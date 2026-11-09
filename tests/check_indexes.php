<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- INDICES ACTUALES EN INVENTORIES ---\n";
$indexes = DB::select('SHOW INDEXES FROM inventories');
foreach ($indexes as $idx) {
    echo "Index: {$idx->Key_name} | Column: {$idx->Column_name} | Unique: " . ($idx->Non_unique == 0 ? 'YES' : 'NO') . "\n";
}
