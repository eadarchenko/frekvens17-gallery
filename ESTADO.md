# Estado técnico y límites

## Implementado

- Catálogo y variantes, carrito, checkout TEST de invitado/cliente e historial privado.
- Sesiones de servidor, CSRF, contraseñas con hash, verificación y recuperación con entrega exclusivamente a log local.
- Pedidos transaccionales, cálculo de importes en servidor, idempotencia y stock no negativo.
- Rol de artista concedido por consola; CRUD de obras, variantes y pedidos.
- Subidas JPG/PNG/WebP: máximo 5 MB y 16 millones de píxeles. El original queda privado; GD genera una copia web de hasta 960 px. No se admiten SVG ni ejecutables.
- Los pedidos de invitado no se reclaman por coincidir el email con una cuenta.
- Interfaz móvil: columnas apiladas, menú de dos filas, controles táctiles de 44 px y campos con letra de 16 px.

## No es una tienda de producción

No hay pagos, proveedor de correo real, facturación, conciliación, reembolsos, certificados numerados ni seguimiento externo de invitado. Administración lista los últimos 500 pedidos; no incluye auditoría de cambios ni paginación avanzada.

Antes del lanzamiento se deben confirmar los derechos de publicación y los textos, precios, stock, moneda, impuestos, envíos, devoluciones y privacidad; integrar y probar pagos y correo con claves privadas/webhooks; elegir alojamiento, HTTPS, cookies seguras, copias y restauración; revisar permisos y dependencias en el destino; probar dispositivos físicos y hacer una revisión independiente de seguridad y accesibilidad.

No usar una SQLite sobre disco efímero como almacenamiento permanente. Una demo que se reinicia debe avisar de ello y no recoger datos personales reales.

## Protección de imágenes

Solo se incluyen copias WebP reducidas. Los originales del catálogo se excluyen de este repositorio. Las nuevas subidas quedan fuera de la raíz web y no tienen ruta de descarga de originales. Una imagen visible siempre puede copiarse o capturarse. No hay DRM ni garantía contra capturas.

## Comprobación del 18 de noviembre de 2026

36 pruebas PHP, 183 aserciones, recorridos de navegador de invitado/cliente/artista y 27 estados en nueve anchos: 320, 390, 768, 800, 801, 850, 851, 1024 y 1280 px. El recorrido móvil usa tacto emulado, no un teléfono físico.

El build tiene avisos de tamaño de bundle/estilos. La revisión de dependencias encontró cero avisos en dependencias npm de producción y Composer. Las herramientas de desarrollo tenían un aviso transitivo de picomatch, no incluido en el JavaScript servido; volver a revisar antes de instalar o desplegar: https://github.com/advisories/GHSA-fc5q-39xc-vjpf. Estas comprobaciones no equivalen a una auditoría independiente.
