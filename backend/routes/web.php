<?php

use App\Modules\Catalog\Http\Controllers\PublicProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Nota: rota pública de compartilhamento movida para routes/api/v1.php (pública)
