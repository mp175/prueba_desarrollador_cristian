<?php

namespace App\Services;

class EmailNotificationService
{
    public function enviar(string $destinatario, array $resumen): bool
    {
        if (empty($destinatario)) {
            log_message('warning', '[EmailNotificationService] No hay destinatario configurado.');
            return false;
        }

        $asunto  = '[Pagos] Resumen de procesamiento - ' . $resumen['fecha_proceso'];
        $cuerpo  = $this->construirCuerpo($resumen);

        $email = \Config\Services::email();
        $email->setFrom(env('NOTIFICACION_EMAIL_FROM', 'noreply@sistema.com'), 'Sistema de Pagos');
        $email->setTo($destinatario);
        $email->setSubject($asunto);
        $email->setMessage($cuerpo);

        $resultado = $email->send();

        if (!$resultado) {
            log_message('error', '[EmailNotificationService] Error al enviar: ' . $email->printDebugger(['headers']));
        }

        return $resultado;
    }

    private function construirCuerpo(array $resumen): string
    {
        $erroresHtml = '';
        if (!empty($resumen['detalle_errores'])) {
            $erroresHtml .= '<h3>Detalle de errores y omisiones:</h3><ul>';
            foreach ($resumen['detalle_errores'] as $e) {
                $erroresHtml .= "<li>Fila {$e['fila']}: " . htmlspecialchars($e['mensaje']) . '</li>';
            }
            $erroresHtml .= '</ul>';
        }

        return "
        <html><body>
        <h2>Resumen de Procesamiento de Pagos</h2>
        <p><strong>Archivo:</strong> {$resumen['archivo']}</p>
        <p><strong>Fecha de proceso:</strong> {$resumen['fecha_proceso']}</p>
        <table border='1' cellpadding='6' cellspacing='0'>
            <tr><td>Total de registros leídos</td><td>{$resumen['total']}</td></tr>
            <tr><td>Registros exitosos</td><td style='color:green'>{$resumen['exitosos']}</td></tr>
            <tr><td>Registros con error</td><td style='color:red'>{$resumen['errores']}</td></tr>
            <tr><td>Duplicados omitidos</td><td style='color:orange'>{$resumen['duplicados']}</td></tr>
        </table>
        {$erroresHtml}
        </body></html>
        ";
    }
}