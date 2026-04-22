<?php

namespace App\Models;

use CodeIgniter\Model;

class PagoModel extends Model
{
    protected $table            = 'pagos';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'documento_cliente', 'fecha_pago', 'monto', 'medio_pago', 'observaciones',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = '';

    protected $validationRules = [
        'documento_cliente' => 'required',
        'fecha_pago'        => 'required|valid_date[Y-m-d]',
        'monto'             => 'required|decimal|greater_than[0]',
        'medio_pago'        => 'required',
    ];

    public function existeDuplicado(array $pago): bool
    {
        return $this->where('documento_cliente', $pago['documento_cliente'])
                    ->where('fecha_pago',        $pago['fecha_pago'])
                    ->where('monto',             $pago['monto'])
                    ->where('medio_pago',        $pago['medio_pago'])
                    ->countAllResults() > 0;
    }
}