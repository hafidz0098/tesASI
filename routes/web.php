<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MyClientController;

Route::resource('clients', MyClientController::class);

