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

        // Fechas del mes seleccionado y anterior
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();
        $prevStart = $start->copy()->subMonth()->startOfMonth();
        $prevEnd = $start->copy()->subMonth()->endOfMonth();

        // Ingresos y gastos del mes actual y anterior
        $ingresosMes = Ingreso::where('user_id', $user->id)->whereBetween('fecha', [$start, $end])->sum('monto');
        $gastosMes = Gasto::where('user_id', $user->id)->whereBetween('fecha', [$start, $end])->sum('monto');
        $ingresosMesAnterior = Ingreso::where('user_id', $user->id)->whereBetween('fecha', [$prevStart, $prevEnd])->sum('monto');
        $gastosMesAnterior = Gasto::where('user_id', $user->id)->whereBetween('fecha', [$prevStart, $prevEnd])->sum('monto');

        // Balance total del mes y comparación con mes anterior
        $balanceMes = $ingresosMes - $gastosMes;
        $balanceMesAnterior = $ingresosMesAnterior - $gastosMesAnterior;
        $balancePorcentaje = $balanceMesAnterior == 0 ? 0 : round((($balanceMes - $balanceMesAnterior) / abs($balanceMesAnterior)) * 100, 1);

        // Porcentaje de gastos respecto al mes anterior
        $gastosPorcentaje = $gastosMesAnterior == 0 ? 0 : round((($gastosMes - $gastosMesAnterior) / abs($gastosMesAnterior)) * 100, 1);

        // Objetivos de ahorro
        $ahorros = Ahorro::where('user_id', $user->id)->get();
        $metaTotalObjetivo = $ahorros->sum('monto_objetivo');
        $metaTotalActual = $ahorros->sum('monto_actual');
        $metaPorcentaje = $metaTotalObjetivo == 0 ? 0 : round(($metaTotalActual / $metaTotalObjetivo) * 100);

        // Distribución por categoría de gastos del mes (porcentaje)
        $categorias = Categoria::where('user_id', $user->id)->get();
        $gastosPorCategoria = $categorias->map(function ($cat) use ($user, $start, $end) {
            $total = Gasto::where('user_id', $user->id)
                ->where('categoria_id', $cat->id)
                ->whereBetween('fecha', [$start, $end])
                ->sum('monto');
            return [
                'categoria' => $cat->nombre,
                'color' => $cat->color ?? '#cccccc',
                'total' => $total,
            ];
        })->filter(fn($c) => $c['total'] > 0)->values();

        $totalGastosMes = $gastosPorCategoria->sum('total');
        $gastosPorCategoria = $gastosPorCategoria->map(function ($cat) use ($totalGastosMes) {
            $porcentaje = $totalGastosMes == 0 ? 0 : round(($cat['total'] / $totalGastosMes) * 100);
            return [
                'categoria' => $cat['categoria'],
                'color' => $cat['color'],
                'porcentaje' => $porcentaje,
            ];
        });

        // Tendencia mensual (últimos 6 meses)
        $tendencia = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $mes = $date->month;
            $anio = $date->year;
            $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
            $fin = Carbon::create($anio, $mes, 1)->endOfMonth();
            $tendencia[] = [
                'mes' => $inicio->format('M'),
                'ingresos' => Ingreso::where('user_id', $user->id)->whereBetween('fecha', [$inicio, $fin])->sum('monto'),
                'gastos' => Gasto::where('user_id', $user->id)->whereBetween('fecha', [$inicio, $fin])->sum('monto'),
            ];
        }

        // Últimos gastos (nombre, hace cuánto, valor)
        $ultimosGastos = Gasto::where('user_id', $user->id)
            ->orderByDesc('fecha')
            ->take(5)
            ->get()
            ->map(function ($g) {
                return [
                    'descripcion' => $g->descripcion,
                    'monto' => $g->monto,
                    'fecha' => $g->fecha,
                    'hace' => Carbon::parse($g->fecha)->diffForHumans(),
                    'categoria' => $g->categoria->nombre ?? null,
                    'color' => $g->categoria->color ?? null,
                ];
            });

        // Objetivos de ahorro (porcentaje y monto actual de cada uno)
        $objetivosAhorro = $ahorros->map(function ($a) {
            $porcentaje = $a->monto_objetivo == 0 ? 0 : round(($a->monto_actual / $a->monto_objetivo) * 100);
            return [
                'nombre' => $a->nombre,
                'monto_actual' => $a->monto_actual,
                'monto_objetivo' => $a->monto_objetivo,
                'porcentaje' => $porcentaje,
                'color' => $a->color ?? null,
            ];
        });

        return [
            'balance_total' => [
                'valor' => $balanceMes,
                'porcentaje_vs_mes_anterior' => $balancePorcentaje,
            ],
            'gastos_mes' => [
                'valor' => $gastosMes,
                'porcentaje_vs_mes_anterior' => $gastosPorcentaje,
            ],
            'ingresos_mes' => $ingresosMes,
            'meta_ahorro' => [
                'porcentaje' => $metaPorcentaje,
                'total_ahorrado' => $metaTotalActual,
                'total_objetivo' => $metaTotalObjetivo,
            ],
            'distribucion_categoria' => $gastosPorCategoria,
            'tendencia_mensual' => $tendencia,
            'movimientos_recientes' => $ultimosGastos,
            'objetivos_ahorro' => $objetivosAhorro,
        ];
    }
}