<?php

namespace App\Services;

use App\Models\PagoModel;
use App\Models\ProcesamientoLogModel;
use App\Libraries\CiValidator;

class PagoProcessorService
{
    private PagoModel $pagoModel;
    private ProcesamientoLogModel $logModel;

    // Medios de pago válidos aceptados
    private const MEDIOS_PAGO_VALIDOS = [
        'efectivo', 'transferencia', 'tarjeta'
    ];

    public function __construct()
    {
        $this->pagoModel = new PagoModel();
        $this->logModel  = new ProcesamientoLogModel();
    }

    public function procesar(string $rutaArchivo): array
    {
        $resumen = [
            'total'           => 0,
            'exitosos'        => 0,
            'errores'         => 0,
            'duplicados'      => 0,
            'detalle_errores' => [],
            'archivo'         => basename($rutaArchivo),
            'fecha_proceso'   => date('Y-m-d H:i:s'),
        ];

        $handle = fopen($rutaArchivo, 'r');
        if ($handle === false) {
            throw new \RuntimeException("No se pudo abrir el archivo: {$rutaArchivo}");
        }

        // Leer encabezados y normalizar
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new \RuntimeException('El archivo CSV está vacío o mal formado.');
        }
        $headers = array_map('trim', array_map('strtolower', $headers));

        $columnaEsperadas = ['documento_cliente', 'fecha_pago', 'monto', 'medio_pago', 'observaciones'];
        $faltantes = array_diff($columnaEsperadas, $headers);
        if (!empty($faltantes)) {
            fclose($handle);
            throw new \RuntimeException('Faltan columnas en el CSV: ' . implode(', ', $faltantes));
        }

        $fila = 1; // la fila 1 fue el encabezado

        while (($row = fgetcsv($handle)) !== false) {
            $fila++;
            $resumen['total']++;

            // Armar array asociativo con los encabezados
            $data = array_combine($headers, array_map('trim', $row));

            $erroresFila = $this->validar($data, $fila);

            if (!empty($erroresFila)) {
                $resumen['errores']++;
                foreach ($erroresFila as $msg) {
                    $resumen['detalle_errores'][] = ['fila' => $fila, 'mensaje' => $msg];
                }
                $this->logModel->registrar($data, 'error', implode(' | ', $erroresFila), $fila);
                continue;
            }

            // Normalizar datos antes de persistir
            $pago = $this->normalizar($data);

            // Verificar duplicado (mismo cliente + fecha + monto + medio_pago)
            if ($this->pagoModel->existeDuplicado($pago)) {
                $resumen['duplicados']++;
                $resumen['detalle_errores'][] = [
                    'fila'    => $fila,
                    'mensaje' => "Duplicado omitido: doc={$pago['documento_cliente']} fecha={$pago['fecha_pago']} monto={$pago['monto']}",
                ];
                $this->logModel->registrar($data, 'duplicado', 'Registro duplicado', $fila);
                continue;
            }

            $this->pagoModel->insert($pago);
            $this->logModel->registrar($data, 'exitoso', null, $fila);
            $resumen['exitosos']++;
        }

        fclose($handle);
        return $resumen;
    }

    private function validar(array $data, int $fila): array
    {
        $errores = [];

        // documento_cliente
        $ciValidator = new CiValidator();
        if (empty($data['documento_cliente'])) {
            $errores[] = 'documento_cliente vacío';
        } elseif (! $ciValidator->validate_ci($data['documento_cliente'])) {
            $errores[] = "documento_cliente inválido: '{$data['documento_cliente']}'";
        }

        // fecha_pago
        if (empty($data['fecha_pago'])) {
            $errores[] = 'fecha_pago vacía';
        } else {
            $fecha = \DateTime::createFromFormat('Y-m-d', $data['fecha_pago'])
                  ?: \DateTime::createFromFormat('d/m/Y', $data['fecha_pago']);
            if (!$fecha) {
                $errores[] = "fecha_pago con formato inválido: '{$data['fecha_pago']}'";
            } elseif ($fecha > new \DateTime()) {
                $errores[] = "fecha_pago futura no permitida: '{$data['fecha_pago']}'";
            }
        }

        // monto
        if (!isset($data['monto']) || $data['monto'] === '') {
            $errores[] = 'monto vacío';
        } else {
            $monto = filter_var(str_replace(',', '.', $data['monto']), FILTER_VALIDATE_FLOAT);
            if ($monto === false || $monto <= 0) {
                $errores[] = "monto inválido o no positivo: '{$data['monto']}'";
            }
        }

        // medio_pago
        if (empty($data['medio_pago'])) {
            $errores[] = 'medio_pago vacío';
        } elseif (!in_array(strtolower($data['medio_pago']), self::MEDIOS_PAGO_VALIDOS, true)) {
            $errores[] = "medio_pago desconocido: '{$data['medio_pago']}'";
        }

        return $errores;
    }

    private function normalizar(array $data): array
    {
        // Unificar formato de fecha a Y-m-d
        $fecha = \DateTime::createFromFormat('Y-m-d', $data['fecha_pago'])
              ?: \DateTime::createFromFormat('d/m/Y', $data['fecha_pago']);

        return [
            'documento_cliente' => $data['documento_cliente'],
            'fecha_pago'        => $fecha->format('Y-m-d'),
            'monto'             => (float) str_replace(',', '.', $data['monto']),
            'medio_pago'        => strtolower($data['medio_pago']),
            'observaciones'     => $data['observaciones'] ?? null,
        ];
    }
}