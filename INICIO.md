# Arranque y cuentas

1. Instala Node.js, PHP 8.3+ con GD/WebP y SQLite, y Composer.
2. Ejecuta `node scripts/setup.cjs` en la raíz del proyecto.
3. Ejecuta `node scripts/start.cjs` para compilar y arrancar el sitio local.

No servir nunca la raíz del proyecto. La única raíz web es `backend/public`. No subir `.env`, SQLite, logs, sesiones ni imágenes originales. No ejecutar `storage:link`: las imágenes de producto se sirven por su ruta controlada.

## Primer artista

Registra una cuenta en la web local. El registro público nunca concede el rol de artista. Para convertir una cuenta local en artista:

```sh
cd backend
php artisan shop:admin TU_EMAIL
```

Sustituye TU_EMAIL por el email de una cuenta ya registrada. Para probar sin crear cuentas normales, usa `node scripts/demo.cjs` desde la raíz, que pide dos claves ocultas y crea datos ficticios en otra base.

## Correo local

La verificación y la recuperación generan enlaces temporales en `backend/storage/logs/laravel.log`. Ese archivo es privado y solo debe consultarse en el ordenador de desarrollo. Una cuenta sin verificar no puede registrar pedidos TEST con sesión iniciada. Los invitados pueden usar el checkout TEST sin crear cuenta cuando está expresamente habilitado.

## Checkout TEST

La configuración inicial desactiva el checkout. Para una prueba local, configura `SHOP_CHECKOUT_MODE=test` y `SHOP_CURRENCY=EUR` en `.env`, reinicia el servidor y usa únicamente productos/datos ficticios. `SHOP_TEST_SHIPPING_CENTS` y `SHOP_TEST_TAX_BASIS_POINTS` son cálculos de prueba, no reglas comerciales.

La demo separada ya trae precios y stock ficticios. No utilizar datos personales ni información de pago reales en ella.
