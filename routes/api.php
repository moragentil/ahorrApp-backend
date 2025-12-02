<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\AhorroController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GrupoGastoController;
use App\Http\Controllers\GastoCompartidoController;
use App\Http\Controllers\AporteGastoController;
use App\Http\Controllers\InvitacionGrupoController;
use App\Http\Controllers\ParticipanteController;

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
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // Ingresos
    Route::get('/ingresos/estadisticas', [IngresoController::class, 'estadisticas']);
    Route::get('/ingresos', [IngresoController::class, 'index']);
    Route::get('/ingresos/{id}', [IngresoController::class, 'show']);
    Route::post('/ingresos', [IngresoController::class, 'store']);
    Route::put('/ingresos/{id}', [IngresoController::class, 'update']);
    Route::delete('/ingresos/{id}', [IngresoController::class, 'destroy']);

    // Gastos
    Route::get('/gastos/top', [GastoController::class, 'topGastos']);
    Route::get('/gastos', [GastoController::class, 'index']);
    Route::get('/gastos/{id}', [GastoController::class, 'show']);
    Route::post('/gastos', [GastoController::class, 'store']);
    Route::put('/gastos/{id}', [GastoController::class, 'update']);
    Route::delete('/gastos/{id}', [GastoController::class, 'destroy']);

    // Categorias
    Route::get('/categorias/gasto', [CategoriaController::class, 'gastoCategorias']);
    Route::get('/categorias/ingreso', [CategoriaController::class, 'ingresoCategorias']);
    Route::get('/categorias/resumen', [CategoriaController::class, 'resumen']);
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

    // Invitaciones a Grupos (ANTES de las rutas de grupos)
    Route::get('/invitaciones', [InvitacionGrupoController::class, 'misInvitaciones']);
    Route::post('/invitaciones/{token}/aceptar', [InvitacionGrupoController::class, 'aceptar']);
    Route::post('/invitaciones/{token}/rechazar', [InvitacionGrupoController::class, 'rechazar']);
    Route::delete('/invitaciones/{invitacionId}', [InvitacionGrupoController::class, 'cancelar']);

    // Participantes (rutas específicas ANTES de las genéricas)
    Route::get('/grupos-gastos/{grupoId}/participantes', [ParticipanteController::class, 'index']);
    Route::post('/participantes', [ParticipanteController::class, 'store']);
    Route::get('/participantes/{id}', [ParticipanteController::class, 'show']);
    Route::put('/participantes/{id}', [ParticipanteController::class, 'update']);
    Route::delete('/participantes/{id}', [ParticipanteController::class, 'destroy']);
    Route::post('/participantes/{id}/vincular', [ParticipanteController::class, 'vincularUsuario']);
    Route::post('/participantes/{id}/asociar-email', [ParticipanteController::class, 'asociarEmail']);

    // Gastos Compartidos (rutas específicas ANTES de las genéricas)
    Route::get('/grupos-gastos/{grupoId}/gastos-compartidos', [GastoCompartidoController::class, 'index']);
    Route::post('/gastos-compartidos', [GastoCompartidoController::class, 'store']);
    Route::get('/gastos-compartidos/{id}', [GastoCompartidoController::class, 'show']);
    Route::put('/gastos-compartidos/{id}', [GastoCompartidoController::class, 'update']);
    Route::delete('/gastos-compartidos/{id}', [GastoCompartidoController::class, 'destroy']);

    // Aportes de Gastos
    Route::get('/gastos-compartidos/{gastoCompartidoId}/aportes', [AporteGastoController::class, 'index']);
    Route::post('/aportes', [AporteGastoController::class, 'store']);
    Route::get('/aportes/{id}', [AporteGastoController::class, 'show']);
    Route::put('/aportes/{id}', [AporteGastoController::class, 'update']);
    Route::delete('/aportes/{id}', [AporteGastoController::class, 'destroy']);
    Route::post('/aportes/{id}/pagar', [AporteGastoController::class, 'registrarPago']);

    // Grupos de Gastos (rutas más específicas primero)
    Route::get('/grupos-gastos/{grupoId}/invitaciones', [InvitacionGrupoController::class, 'pendientes']);
    Route::post('/grupos-gastos/{grupoId}/invitar', [InvitacionGrupoController::class, 'enviarInvitacion']);
    Route::post('/grupos-gastos/{grupoId}/generar-enlace-invitacion', [InvitacionGrupoController::class, 'generarEnlace']);
    Route::get('/grupos-gastos/{id}/balances', [GrupoGastoController::class, 'balances']);
    Route::post('/grupos-gastos/{grupoId}/registrar-pago-balance', [GrupoGastoController::class, 'registrarPagoBalance']);

    
    // Grupos de Gastos (CRUD básico al final)
    Route::get('/grupos-gastos', [GrupoGastoController::class, 'index']);
    Route::post('/grupos-gastos', [GrupoGastoController::class, 'store']);
    Route::get('/grupos-gastos/{id}', [GrupoGastoController::class, 'show']);
    Route::put('/grupos-gastos/{id}', [GrupoGastoController::class, 'update']);
    Route::delete('/grupos-gastos/{id}', [GrupoGastoController::class, 'destroy']);

    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    Route::get('/dashboard', [DashboardController::class, 'home']);
});