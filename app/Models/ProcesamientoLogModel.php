<?php

namespace App\Models;

use CodeIgniter\Model;

class ProcesamientoLogModel extends Model
{
    protected $table         = 'procesamiento_log';
    protected $allowedFields = [
        'fila_csv', 'documento_cliente', 'fecha_pago',
        'monto', 'medio_pago', 'estado', 'mensaje_error',
    ];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    public function registrar(array $data, string $estado, ?string $error, int $fila): void
    {
        $this->insert([
            'fila_csv'          => $fila,
            'documento_cliente' => $data['documento_cliente'] ?? null,
            'fecha_pago'        => $data['fecha_pago']        ?? null,
            'monto'             => $data['monto']             ?? null,
            'medio_pago'        => $data['medio_pago']        ?? null,
            'estado'            => $estado,
            'mensaje_error'     => $error,
        ]);
    }
}