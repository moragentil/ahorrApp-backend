<?php

namespace App\Services\Implementation;

use App\Models\AporteGasto;
use App\Services\Interface\AporteGastoServiceInterface;

class AporteGastoService implements AporteGastoServiceInterface
{
    public function all($gastoCompartidoId)
    {
        return AporteGasto::where('gasto_compartido_id', $gastoCompartidoId)
            ->with('usuario')
            ->get();
    }

    public function find($id)
    {
        return AporteGasto::with(['usuario', 'gasto'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return AporteGasto::create($data);
    }

    public function update($id, array $data)
    {
        $aporte = AporteGasto::findOrFail($id);
        $aporte->update($data);
        return $aporte->fresh(['usuario', 'gasto']);
    }

    public function delete($id)
    {
        $aporte = AporteGasto::findOrFail($id);
        $aporte->delete();
        return true;
    }

    public function registrarPago($id, $montoPagado)
    {
        $aporte = AporteGasto::findOrFail($id);
        $aporte->update([
            'monto_pagado' => $aporte->monto_pagado + $montoPagado
        ]);
        return $aporte->fresh(['usuario', 'gasto']);
    }
}