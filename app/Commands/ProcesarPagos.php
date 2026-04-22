<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\PagoProcessorService;
use App\Services\EmailNotificationService;

class ProcesarPagos extends BaseCommand
{
    protected $group       = 'Pagos';
    protected $name        = 'pagos:procesar';
    protected $description = 'Procesa un archivo CSV de pagos y persiste los datos en la base de datos.';
    protected $usage       = 'pagos:procesar [opciones]';
    protected $options     = [
        '--archivo' => 'Ruta al archivo CSV. Por defecto: writable/uploads/Datos de prueba de pagos.csv',
        '--email'   => 'Email destinatario del resumen.',
    ];

    public function run(array $params): void
    {
        $archivo = CLI::getOption('archivo') ?? WRITEPATH . 'uploads/Datos de prueba de pagos.csv';
        $email   = CLI::getOption('email')   ?? env('NOTIFICACION_EMAIL_DESTINO', '');

        CLI::write('=== Procesador de Pagos ===', 'cyan');
        CLI::write('Archivo: ' . $archivo);
        CLI::newLine();

        if (!file_exists($archivo)) {
            CLI::error("El archivo no existe: {$archivo}");
            CLI::write('Uso: php spark pagos:procesar --archivo=/ruta/al/archivo.csv');
            return;
        }

        $processor = new PagoProcessorService();

        CLI::write('Procesando registros...', 'yellow');
        $resumen = $processor->procesar($archivo);

        // Resumen en consola
        CLI::newLine();
        CLI::write('=== Resumen del Procesamiento ===', 'cyan');
        CLI::write('Total de registros leídos : ' . $resumen['total']);
        CLI::write('Registros exitosos        : ' . $resumen['exitosos'], 'green');
        CLI::write('Registros con error       : ' . $resumen['errores'], $resumen['errores'] > 0 ? 'red' : 'green');
        CLI::write('Duplicados omitidos       : ' . $resumen['duplicados'], 'yellow');

        if (!empty($resumen['detalle_errores'])) {
            CLI::newLine();
            CLI::write('Detalle de errores:', 'red');
            foreach ($resumen['detalle_errores'] as $error) {
                CLI::write("  Fila {$error['fila']}: {$error['mensaje']}");
            }
        }

        // Email
        CLI::newLine();
        CLI::write('Enviando notificación por email...', 'yellow');

        $notificador = new EmailNotificationService();
        $enviado     = $notificador->enviar($email, $resumen);

        if ($enviado) {
            CLI::write('Email enviado correctamente.', 'green');
        } else {
            CLI::write('No se pudo enviar el email (revisar configuración).', 'red');
        }

        CLI::newLine();
        CLI::write('Proceso finalizado.', 'cyan');
    }
}