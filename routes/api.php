<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\AhorroController;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // User
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Ingresos
    Route::get('/ingresos', [IngresoController::class, 'index']);
    Route::get('/ingresos/{id}', [IngresoController::class, 'show']);
    Route::post('/ingresos', [IngresoController::class, 'store']);
    Route::put('/ingresos/{id}', [IngresoController::class, 'update']);
    Route::delete('/ingresos/{id}', [IngresoController::class, 'destroy']);

    // Gastos
    Route::get('/gastos', [GastoController::class, 'index']);
    Route::get('/gastos/{id}', [GastoController::class, 'show']);
    Route::post('/gastos', [GastoController::class, 'store']);
    Route::put('/gastos/{id}', [GastoController::class, 'update']);
    Route::delete('/gastos/{id}', [GastoController::class, 'destroy']);

    // Categorias
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/{id}', [CategoriaController::class, 'show']);
    Route::post('/categorias', [CategoriaController::class, 'store']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);

    // Ahorros
    Route::get('/ahorros', [AhorroController::class, 'index']);
    Route::get('/ahorros/{id}', [AhorroController::class, 'show']);
    Route::post('/ahorros', [AhorroController::class, 'store']);
    Route::put('/ahorros/{id}', [AhorroController::class, 'update']);
    Route::delete('/ahorros/{id}', [AhorroController::class, 'destroy']);

    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });
});