# Frekvens 17 · galería y tienda de demostración

Angular 22 + Laravel 13 + SQLite. Diseño adaptable a escritorio y móvil: catálogo, fichas de obra, carrito, cuentas de clientes y panel de artista para obras y pedidos.

## Ejecutar en local

Requisitos: Node.js compatible con Angular 22, PHP 8.3 o posterior con SQLite, GD/WebP y extensiones requeridas por Laravel, y Composer.

```sh
node scripts/setup.cjs
node scripts/start.cjs
```

El instalador prepara `.env`, la clave de aplicación y SQLite solo en tu ordenador. El checkout está desactivado por defecto. La web local está en `http://127.0.0.1:8000`.

### Demo con datos ficticios

```sh
node scripts/demo.cjs
```

Elige dos claves en los prompts ocultos de la terminal. Se crea otra base SQLite con precios ficticios desde 300 EUR, cuentas `artist@demo.test` y `collector@demo.test`, un pedido completado simulado y un carrito pendiente. No se modifica la base normal. No hay contraseñas de acceso predeterminadas.

## Límites importantes

- No hay pasarela de pago, cobros, reembolsos, facturas ni envíos físicos. Los pedidos son TEST.
- La verificación de email, recuperación y confirmación de pedido usan exclusivamente un log local. Nunca se envía correo externo en esta versión. Los logs pueden contener enlaces temporales privados: no compartirlos.
- Los textos comerciales del catálogo no son condiciones de venta confirmadas. Precios, existencias, fiscalidad, envíos y textos legales requieren revisión antes de vender.
- Este repositorio contiene solo copias web reducidas de las imágenes, de hasta 960 px en el lado largo. Los originales no están aquí. Las copias visibles se pueden guardar o capturar.
- La visibilidad pública del código no concede una licencia nueva sobre las obras, fotografías ni identidad de la artista. No se añade una licencia artística ni una licencia de software por defecto.
- El repositorio no es un alojamiento PHP: abrirlo en GitHub no ejecuta el backend.

## Pruebas

```sh
cd backend
php artisan test
cd ..
node scripts/e2e.cjs
node scripts/demo-e2e.cjs
node scripts/mobile-e2e.cjs
```

Las pruebas de navegador usan Chrome/Chromium instalado mediante Playwright o `CHROME_BIN`; `PHP_BINARY` permite indicar la ruta de PHP. Crean bases temporales de prueba. No deben ejecutarse contra una base de producción.

Revisión móvil en Chrome con tacto emulado: 27 estados, de 320 a 1280 px, menú y acciones móviles con zonas de al menos 44 px. Sigue pendiente probar Safari/iOS y Android en dispositivos físicos y hacer una auditoría completa de accesibilidad.

Consulta [INICIO.md](INICIO.md) para crear el primer acceso de artista y [ESTADO.md](ESTADO.md) para los límites técnicos y de despliegue.

## Public hosted demo

The Dockerfile starts an isolated, temporary test store. Each browser gets a separate SQLite copy with fictional sample roles. Use the banner buttons to try collector and artist. Checkout never charges anything. Personal account registration, email, profile editing and image uploads are disabled in this mode. Never enter personal information. Changes reset when the free service restarts or after the temporary database expires. This is not a persistent store for real sales.

Render: create a Web Service from this public repository, choose Docker, the Free ($0) instance, and port 80. No environment secrets or payment card are needed. APP_KEY is generated on startup. Set health check path to `/up`. Do not attach a paid disk or database.
