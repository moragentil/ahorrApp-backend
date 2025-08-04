<?php

namespace App\Services\Implementation;

use App\Models\Ingreso;
use App\Services\Interface\IngresoServiceInterface;
use App\Models\Categoria;
use Carbon\Carbon;
use App\Models\User;

class IngresoService implements IngresoServiceInterface
{
    public function all()
    {
        return Ingreso::with('categoria')->get();
    }

    public function find($id)
    {
        return Ingreso::with('categoria')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Ingreso::create($data);
    }

    public function update($id, array $data)
    {
        $ingreso = Ingreso::findOrFail($id);
        $ingreso->update($data);
        return $ingreso;
    }

    public function delete($id)
    {
        $ingreso = Ingreso::findOrFail($id);
        $ingreso->delete();
        return true;
    }

    /**
     * Devuelve datos para gráficos de ingresos:
     * - tendencia mensual últimos 6 meses
     * - distribución por categoría del mes actual
     */
    public function estadisticas(User $user, $month = null, $year = null)
    {
        $now = Carbon::now();
        $month = $month ?? $now->month;
        $year = $year ?? $now->year;

        // Fechas del mes seleccionado
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        // Tendencia de ingresos últimos 6 meses
        $tendencia = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $mes = $date->month;
            $anio = $date->year;
            $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
            $fin = Carbon::create($anio, $mes, 1)->endOfMonth();
            $tendencia[] = [
                'mes' => $inicio->format('M'),
                'total' => Ingreso::where('user_id', $user->id)->whereBetween('fecha', [$inicio, $fin])->sum('monto'),
            ];
        }

        // Distribución por categoría de ingresos del mes actual
        $categorias = Categoria::where('user_id', $user->id)->where('tipo', 'ingreso')->get();
        $ingresosPorCategoria = $categorias->map(function ($cat) use ($user, $start, $end) {
            $total = Ingreso::where('user_id', $user->id)
                ->where('categoria_id', $cat->id)
                ->whereBetween('fecha', [$start, $end])
                ->sum('monto');
            return [
                'categoria' => $cat->nombre,
                'color' => $cat->color ?? '#cccccc',
                'total' => $total,
            ];
        })->filter(fn($c) => $c['total'] > 0)->values();

        $totalIngresosMes = $ingresosPorCategoria->sum('total');
        $ingresosPorCategoria = $ingresosPorCategoria->map(function ($cat) use ($totalIngresosMes) {
            $porcentaje = $totalIngresosMes == 0 ? 0 : round(($cat['total'] / $totalIngresosMes) * 100, 1);
            return [
                'categoria' => $cat['categoria'],
                'color' => $cat['color'],
                'total' => $cat['total'],
                'porcentaje' => $porcentaje,
            ];
        });

        return [
            'tendencia_ingresos' => $tendencia,
            'distribucion_categoria' => $ingresosPorCategoria,
        ];
    }
}