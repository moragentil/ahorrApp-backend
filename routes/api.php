<?php
// filepath: c:\Users\morag\Desktop\TP5\ahorrApp-backend\routes\api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\AhorroController;
use App\Http\Controllers\GrupoGastoController;
use App\Http\Controllers\GastoCompartidoController;
use App\Http\Controllers\AporteGastoController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Gastos
    Route::apiResource('gastos', GastoController::class);
    Route::get('/gastos-top', [GastoController::class, 'topGastos']);

    // Categorías
    Route::apiResource('categorias', CategoriaController::class);
    Route::get('/categorias-gasto', [CategoriaController::class, 'getGastoCategorias']);
    Route::get('/categorias-ingreso', [CategoriaController::class, 'getIngresoCategorias']);

    // Ingresos
    Route::apiResource('ingresos', IngresoController::class);

    // Ahorros
    Route::apiResource('ahorros', AhorroController::class);

    // ========== GASTOS COMPARTIDOS ==========

    // Grupos de gastos
    Route::apiResource('grupos-gastos', GrupoGastoController::class);
    Route::post('/grupos-gastos/{id}/participantes', [GrupoGastoController::class, 'addParticipante']);
    Route::delete('/grupos-gastos/{id}/participantes', [GrupoGastoController::class, 'removeParticipante']);
    Route::get('/grupos-gastos/{id}/balances', [GrupoGastoController::class, 'balances']);

    // Gastos compartidos
    Route::get('/grupos-gastos/{grupoId}/gastos-compartidos', [GastoCompartidoController::class, 'index']);
    Route::apiResource('gastos-compartidos', GastoCompartidoController::class);
    Route::post('/gastos-compartidos/{id}/aportes', [GastoCompartidoController::class, 'registrarAportes']);

    // Aportes
    Route::get('/gastos-compartidos/{gastoCompartidoId}/aportes', [AporteGastoController::class, 'index']);
    Route::apiResource('aportes-gastos', AporteGastoController::class);
    Route::put('/aportes-gastos/{id}/pagar', [AporteGastoController::class, 'registrarPago']);
});

Route::get('/', function () {
    return response()->json(['message' => 'AhorrApp API']);
});
