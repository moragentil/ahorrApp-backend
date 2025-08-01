<?php

namespace App\Services\Implementation;

use App\Models\User;
use App\Models\Ingreso;
use App\Models\Gasto;
use App\Models\Ahorro;
use App\Models\Categoria;
use Carbon\Carbon;

class DashboardService
{
    public function getHomeData(User $user, $month = null, $year = null)
    {
        $now = Carbon::now();
        $month = $month ?? $now->month;
        $year = $year ?? $now->year;

        // Fechas del mes seleccionado
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        // Movimientos recientes (últimos 3 ingresos/gastos)
        $recent = Gasto::where('user_id', $user->id)
            ->whereBetween('fecha', [$start, $end])
            ->orderByDesc('fecha')
            ->take(3)
            ->get()
            ->map(function ($g) {
                return [
                    'tipo' => 'gasto',
                    'descripcion' => $g->descripcion,
                    'monto' => $g->monto,
                    'fecha' => $g->fecha,
                ];
            })
            ->concat(
                Ingreso::where('user_id', $user->id)
                    ->whereBetween('fecha', [$start, $end])
                    ->orderByDesc('fecha')
                    ->take(3)
                    ->get()
                    ->map(function ($i) {
                        return [
                            'tipo' => 'ingreso',
                            'descripcion' => $i->descripcion,
                            'monto' => $i->monto,
                            'fecha' => $i->fecha,
                        ];
                    })
            )
            ->sortByDesc('fecha')
            ->take(3)
            ->values();

        // Balance total
        $balance = Ingreso::where('user_id', $user->id)->sum('monto') - Gasto::where('user_id', $user->id)->sum('monto');

        // Gastos del mes
        $gastosMes = Gasto::where('user_id', $user->id)->whereBetween('fecha', [$start, $end])->sum('monto');

        // Ingresos del mes
        $ingresosMes = Ingreso::where('user_id', $user->id)->whereBetween('fecha', [$start, $end])->sum('monto');

        // Objetivos de ahorro
        $ahorros = Ahorro::where('user_id', $user->id)->get();

        // Meta de ahorro (ejemplo: primer ahorro activo)
        $metaAhorro = $ahorros->where('estado', 'Activo')->first();
        $metaPorcentaje = $metaAhorro
            ? round(($metaAhorro->monto_actual / $metaAhorro->monto_objetivo) * 100)
            : 0;

        // Distribución por categoría (gastos del mes)
        $categorias = Categoria::where('user_id', $user->id)->get();
        $gastosPorCategoria = $categorias->map(function ($cat) use ($user, $start, $end) {
            $total = Gasto::where('user_id', $user->id)
                ->where('categoria_id', $cat->id)
                ->whereBetween('fecha', [$start, $end])
                ->sum('monto');
            return [
                'categoria' => $cat->nombre,
                'total' => $total,
            ];
        })->filter(fn($c) => $c['total'] > 0)->values();

        // Tendencia mensual (últimos 6 meses)
        $tendencia = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $mes = $date->month;
            $anio = $date->year;
            $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
            $fin = Carbon::create($anio, $mes, 1)->endOfMonth();
            $tendencia[] = [
                'mes' => $inicio->format('F'),
                'ingresos' => Ingreso::where('user_id', $user->id)->whereBetween('fecha', [$inicio, $fin])->sum('monto'),
                'gastos' => Gasto::where('user_id', $user->id)->whereBetween('fecha', [$inicio, $fin])->sum('monto'),
            ];
        }

        return [
            'balance_total' => $balance,
            'gastos_mes' => $gastosMes,
            'ingresos_mes' => $ingresosMes,
            'meta_ahorro' => [
                'porcentaje' => $metaPorcentaje,
                'actual' => $metaAhorro?->monto_actual ?? 0,
                'objetivo' => $metaAhorro?->monto_objetivo ?? 0,
            ],
            'movimientos_recientes' => $recent,
            'objetivos_ahorro' => $ahorros,
            'distribucion_categoria' => $gastosPorCategoria,
            'tendencia_mensual' => $tendencia,
        ];
    }
}