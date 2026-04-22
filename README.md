# Procesador de Pagos — CodeIgniter 4 + MariaDB

## Cómo ejecutar

### 1. Requisitos
- PHP 8.1+
- MariaDB / MySQL

### 2. Instalación
No es necesario instalar, todo viene integrado en el proyecto
- Configurar el archivo .env con la informacion necesaria para conectarce a la db, host, username y password
- Configurar el archivo .env con el mail destinatario. En este caso, se utilizo un servicio mockeado, por lo que se espera que no llegue el mail al destinatario

### 3. Crear la base de datos y ejecutar migraciones
- Crear la base de datos con nombre pagos_db
Ejecutar el siguiente comando
```bash
php spark migrate
```

### 4. Colocar el archivo CSV
Copiar el CSV a procesar en `writable/uploads/Datos de prueba de pagos.csv`
(o indicar la ruta con la opción `--archivo`).

### 5. Ejecutar el comando
```bash
# Usando la ruta por defecto (writable/uploads/pagos.csv)
php spark pagos:procesar

# Especificando archivo y email destinatario
php spark pagos:procesar --archivo=/ruta/pagos.csv --email=admin@empresa.com
```

### 6. Ver ayuda del comando
```bash
php spark pagos:procesar --help
```

---

## Decisiones técnicas

- **Comando Spark**: se eligió el CLI de CodeIgniter para seguir las convenciones del framework y evitar exponer el proceso vía HTTP.
- **Servicio separado (`PagoProcessorService`)**: mantiene el comando delgado y la lógica de negocio testeable de forma independiente.
- **Tabla `procesamiento_log`**: permite auditar qué pasó con cada fila del CSV (exitoso / error / duplicado), útil para debugging y trazabilidad.
- **Detección de duplicados**: se considera duplicado a un registro con mismo `documento_cliente + fecha_pago + monto + medio_pago`. Los duplicados no se rechazan como error sino que se omiten silenciosamente y se registran en el log.
- **Normalización de fechas**: se aceptan tanto `Y-m-d` como `d/m/Y`, unificando a `Y-m-d` antes de persistir.
- **Email con HTML**: se usa el servicio nativo de CI4, configurable vía `.env`. Para desarrollo se recomienda Mailtrap.

## Supuestos

- El campo `observaciones` es opcional; puede venir vacío.
- Los medios de pago válidos son: `efectivo`, `transferencia`, `tarjeta`. Se pueden ampliar en la constante del servicio.
- El `documento_cliente` se valida con una libreria de validacion de cedulas de identidad uruguayas.
- No se permiten fechas futuras en `fecha_pago`.

## Qué mejoraría para producción

- **Procesamiento por lotes (chunks)**: para archivos grandes, leer el CSV en bloques y usar transacciones de BD por bloque, evitando timeouts y consumo excesivo de memoria.
- **Queue / Jobs**: mover el procesamiento a una cola asíncrona (ej. con `php-enqueue` o Codeigniter Queue) para no bloquear el proceso principal.
- **Reintentos**: marcar registros fallidos para reprocesamiento sin necesidad de releer el CSV completo.
- **Tests automatizados**: agregar tests unitarios para `PagoProcessorService` y tests de integración para el comando.
- **Validación de tamaño y encoding**: verificar encoding UTF-8 y límite de tamaño del archivo antes de procesar.
- **Monitoreo**: integrar con un sistema de alertas (Sentry, Datadog) para errores críticos en el proceso.