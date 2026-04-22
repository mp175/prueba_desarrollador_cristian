<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePagosTable extends Migration
{
    public function up(): void
    {
        // Tabla principal de pagos
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'documento_cliente' => ['type' => 'VARCHAR', 'constraint' => 20],
            'fecha_pago'        => ['type' => 'DATE'],
            'monto'             => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'medio_pago'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'observaciones'     => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        // Índice para detección de duplicados
        $this->forge->addKey(['documento_cliente', 'fecha_pago', 'monto', 'medio_pago']);
        $this->forge->createTable('pagos');

        // Tabla de log por registro
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fila_csv'          => ['type' => 'INT', 'constraint' => 6],
            'documento_cliente' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'fecha_pago'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'monto'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'medio_pago'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'estado'            => ['type' => 'ENUM', 'constraint' => ['exitoso', 'error', 'duplicado']],
            'mensaje_error'     => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('procesamiento_log');
    }

    public function down(): void
    {
        $this->forge->dropTable('pagos', true);
        $this->forge->dropTable('procesamiento_log', true);
    }
}