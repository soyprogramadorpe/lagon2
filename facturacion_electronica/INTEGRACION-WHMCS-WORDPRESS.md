# Guía de Integración — Módulo WHMCS y Plugin WordPress

Este documento resume todo lo que necesitas para construir un módulo de facturación de WHMCS
y/o un plugin de WordPress que se conecten a este sistema de Facturación Electrónica (SUNAT
Perú). Complementa la documentación interactiva en `/api-docs` del dashboard (ahí puedes copiar
los payloads de ejemplo directamente).

---

## 1. Conceptos clave antes de empezar

- **Multi-empresa por RUC**: el sistema no tiene un solo "cliente", tiene una empresa (RUC) por
  cada negocio que emite comprobantes. Cada RUC tiene su propio Token API, sus propias series
  (F001, B001, etc.) y su propio webhook.
- **Autenticación por Bearer Token**: cada empresa genera un token único desde el dashboard
  (`Empresas → tu RUC → pestaña Credenciales API → Token de Acceso API`). Ese token identifica
  automáticamente al RUC — no hace falta mandar el RUC por separado en los endpoints de emisión.
- **Beta vs Producción**: cada empresa elige su propio ambiente (`Beta/Pruebas` o `Producción`)
  desde su ficha en el dashboard. Es independiente por empresa — no es una variable global del
  sistema. En Beta, los comprobantes van al sandbox de SUNAT (no tienen validez legal). En
  Producción, van al SUNAT real.
- **Todo corre sobre HTTPS/REST + JSON**. No hay SOAP ni XML de tu lado — el sistema arma y firma
  el XML UBL 2.1 internamente; tú solo mandas/recibes JSON.

### Campos que tu plugin/módulo debe pedir al configurarse

| Campo | Dónde se obtiene | Notas |
|---|---|---|
| **URL base del sistema** | La define quien lo despliega, ej. `https://facturacion.tudominio.com` | No hay un valor fijo — apunta al dominio real del despliegue del cliente |
| **Correo y contraseña del usuario** (solo durante la conexión inicial) | Los del propio usuario de la aplicación de facturación | Se usan una única vez para el flujo de "Conectar Cuenta" (sección 2) — no se guardan |
| **Empresa (RUC) elegida** | El usuario la elige de una lista, dentro del propio flujo de conexión | Si el usuario solo tiene una empresa, se puede saltar el selector |
| **Token API** | Se obtiene automáticamente al completar el flujo de conexión (ya no hace falta copiarlo a mano desde el dashboard) | Es lo único que tu plugin necesita guardar de forma persistente. Se manda como `Authorization: Bearer {token}` en cada request |
| **Serie a usar por tipo de documento** | Se consulta con `GET /api/companies/{ruc}/series` (ver sección 5) | Idealmente un selector poblado dinámicamente, no un campo de texto libre |
| **URL de webhook de tu plugin** (opcional pero recomendado) | La define tu propio plugin/módulo, se configura en el dashboard | Para recibir avisos push en vez de estar preguntando (polling) |
| **Secreto de webhook** | Dashboard → Empresas → tu RUC → Credenciales API → Webhooks → "Revelar Secreto" | Para verificar que las notificaciones sean auténticas (HMAC-SHA256) |

---

## 2. Flujo de conexión (login + elegir empresa)

Un usuario de la aplicación de facturación puede tener acceso a **varias empresas** (RUCs). En
vez de que tenga que ir al dashboard, entrar a cada empresa y copiar su token a mano, tu
plugin/módulo puede resolver todo esto en su propia pantalla de configuración con 2 llamadas:

### Paso 1 — Listar las empresas del usuario
```
POST /api/auth/connect/empresas
Content-Type: application/json

{ "email": "usuario@tuempresa.com", "pass": "su-contraseña-de-la-app" }
```
```json
{
  "success": true,
  "empresas": [
    { "ruc": "20612690601", "razonSocial": "GRUPO REDE IT & WEB SOLUTIONS E.I.R.L." },
    { "ruc": "20600302524", "razonSocial": "EMPRESA DOS S.A.C." }
  ]
}
```
Muéstrale esta lista al usuario en un selector. Si solo tiene una empresa, puedes saltarte la
pantalla y pasar directo al paso 2 con esa única opción.

### Paso 2 — Elegir la empresa y obtener su token
```
POST /api/auth/connect/token
Content-Type: application/json

{ "email": "usuario@tuempresa.com", "pass": "su-contraseña-de-la-app", "ruc": "20612690601" }
```
```json
{
  "success": true,
  "ruc": "20612690601",
  "razonSocial": "GRUPO REDE IT & WEB SOLUTIONS E.I.R.L.",
  "apiToken": "3f9a1c...(64 caracteres hex)"
}
```

**Esto es lo único que tu plugin guarda de forma persistente: el `apiToken`.** El correo y la
contraseña del usuario se usan una sola vez, en el momento de conectar, y no hace falta
conservarlos — de ahí en adelante todo funciona con el Bearer Token normal (secciones 3 en
adelante).

### Detalles importantes
- Ambos pasos re-verifican el correo/contraseña de forma independiente (no hay una sesión ni un
  "ticket" intermedio entre el paso 1 y el 2) — es intencional, para que el endpoint sea simple y
  sin estado.
- Comparten el mismo freno de fuerza bruta que el login del dashboard: **5 intentos fallidos por
  cuenta / 5 min**, y **20 por IP / 5 min**. Si se excede, responde `429` con header `Retry-After`.
- Si el usuario nunca completó su primer login en el dashboard (cuenta con contraseña temporal),
  el paso 1 lo rechaza con `400` pidiéndole que lo haga ahí primero — el flujo de conexión no
  incluye la pantalla de "cambiar contraseña temporal".
- Si en algún momento quieres cambiar de empresa dentro de la misma instalación (por ejemplo, un
  WHMCS que factura para 2 negocios distintos según el producto vendido), simplemente repite el
  Paso 2 con el otro RUC y guarda ambos tokens — no hay límite de conexiones por usuario.
- Si el usuario cambia su contraseña de la aplicación después de conectar, **no afecta al token
  ya obtenido** — el token es independiente de la contraseña una vez emitido. Solo se necesitaría
  volver a pasar por este flujo si quieres conectar una empresa nueva o el token se regeneró/dejó
  de servir.

---

## 3. Autenticación (uso normal, después de conectar)

Todas las peticiones a los endpoints de emisión y consulta de series llevan este header:

```
Authorization: Bearer {TOKEN_DE_LA_EMPRESA}
Content-Type: application/json
```

Si el token falta o es inválido, la respuesta es `401` con:
```json
{ "estado": "ERROR", "mensaje": "No autorizado. Token inválido." }
```

**Importante**: el endpoint de consulta de ticket (`/api/comprobantes/acciones`) es la única
excepción — usa la sesión del dashboard (cookie), no Bearer Token. Está pensado para uso interno
del panel administrativo, no para integraciones externas. Tu plugin puede lograr el mismo
resultado esperando el webhook `documento.actualizado` en vez de llamarlo directamente.

---

## 4. Endpoints de emisión de comprobantes

### 4.1. Factura / Boleta / Nota de Crédito / Nota de Débito / Comunicación de Baja

```
POST /api/comprobantes/enviar
```

Un solo endpoint para 5 tipos de documento, diferenciados por `comprobante.tipo`:

| tipo | Documento | Resolución |
|---|---|---|
| `01` | Factura | Síncrona (SUNAT responde en la misma petición) |
| `03` | Boleta | Síncrona |
| `07` | Nota de Crédito | Síncrona |
| `08` | Nota de Débito | Síncrona |
| `RA` | Comunicación de Baja (anular un comprobante) | **Asíncrona** — solo devuelve un ticket, hay que esperar el resultado (ver sección 6) |

**Ejemplo de payload (Factura/Boleta, tipo 01/03):**
```json
{
  "emisor": { "ruc": "20612690601" },
  "receptor": {
    "tipo_documento": "6",
    "ruc": "20100070701",
    "razon_social": "CLIENTE S.A.C.",
    "direccion": "Av. Principal 123",
    "departamento": "LIMA",
    "provincia": "LIMA",
    "distrito": "LIMA"
  },
  "comprobante": {
    "tipo": "01",
    "tipo_venta": "0101",
    "serie": "F001",
    "fecha_emision": "2026-07-05",
    "hora_emision": "10:00:00",
    "fecha_vencimiento": "2026-08-05",
    "moneda": "PEN",
    "forma_pago": "Contado",
    "total_op_gravadas": 100.00,
    "total_op_exoneradas": 0.00,
    "total_op_inafectas": 0.00,
    "igv": 18.00,
    "icbper": 0.00,
    "total_antes_impuestos": 100.00,
    "total_impuestos": 18.00,
    "total_despues_impuestos": 118.00,
    "total_a_pagar": 118.00
  },
  "items": [
    {
      "item": 1,
      "cantidad": 1,
      "unidad": "NIU",
      "nombre": "Hosting Plan Premium - Julio 2026",
      "valor_unitario": 100.00,
      "precio_lista": 118.00,
      "valor_total": 100.00,
      "igv": 18.00,
      "porcentaje_igv": 18,
      "icbper": 0.00,
      "factor_icbper": 0.00,
      "total_antes_impuestos": 100.00,
      "total_impuestos": 18.00,
      "codigos": ["S", "10", "1000", "IGV", "VAT"]
    }
  ]
}
```

**Notas clave para WHMCS/WordPress:**
- `emisor` solo requiere `ruc` — el resto (razón social, dirección, ubigeo, usuario/clave SOL) se
  autocompleta desde la ficha de la empresa. No necesitas guardar esos datos en tu plugin.
- `comprobante.correlativo` **no se manda** — se reserva automáticamente el siguiente número
  disponible para la serie elegida (a prueba de duplicados, incluso con envíos concurrentes). Si
  tu plugin necesita un correlativo específico (ej. reenvío manual), sí lo puedes mandar
  explícito y se respeta.
- `receptor.tipo_documento`: `1` = DNI, `6` = RUC, `0` = Sin documento (venta menor a un límite).
- Mapeo típico WHMCS: una factura de WHMCS (`tblinvoices`) se traduce 1:1 a un `items[]` con una
  línea por cada `tblinvoiceitems`, y el total se saca de `total`/`subtotal`/`tax`.

**Respuesta (200 OK):**
```json
{
  "estado": "OK",
  "mensaje": "Procesado correctamente",
  "archivo": "20612690601-01-F001-00000103",
  "xmlUrl": "/data/xml/20612690601/20612690601-01-F001-00000103.xml",
  "cdrUrl": "/data/cdr/20612690601/R-20612690601-01-F001-00000103.zip",
  "pdfUrl": "/data/pdf/20612690601/20612690601-01-F001-00000103.pdf",
  "cdrXmlExtracted": "<ar:ApplicationResponse ...>La Factura numero F001-00000103, ha sido aceptada</ar:ApplicationResponse>",
  "respuestaSunat": "<soap-env:Envelope>...</soap-env:Envelope>"
}
```

`pdfUrl` es la representación impresa lista para mostrarle al cliente final o adjuntar a un
correo — constrúyela como `{URL_BASE}{pdfUrl}` (es una ruta relativa).

Para **Nota de Crédito/Débito** (tipos `07`/`08`), el payload es igual pero además necesitas:
```json
"tipo_comp_ref": "01",
"serie_comp_ref": "F001",
"correlativo_comp_ref": "00000101",
"codigo_motivo": "01",
"descripcion_motivo": "ANULACION DE LA OPERACION"
```
(referenciando el comprobante original que se está corrigiendo/anulando). Ver catálogos completos
de `codigo_motivo` en `/api-docs`.

Para **Comunicación de Baja** (tipo `RA`), ver sección 6 (es asíncrona).

### 4.2. Guía de Remisión (traslado de mercadería)

```
POST /api/comprobantes/guia
```

Solo la necesitas si tu integración también maneja envíos físicos de productos (no aplica a la
mayoría de facturación de hosting/dominios en WHMCS). Es asíncrona igual que la Baja, aunque
suele resolver rápido. Ver `/api-docs` pestaña "Guías de Remisión (GRE)" para el payload completo.

---

## 5. Consultar series disponibles

```
GET /api/companies/{ruc}/series
```

Para que tu plugin le muestre al usuario un selector con las series reales configuradas (F001,
B001, FC01, FD01, T001, etc.) en vez de un campo de texto libre donde puede equivocarse.

**Respuesta:**
```json
{
  "series": [
    { "id": 1, "ruc": "20612690601", "tipoDocumento": "01", "serie": "F001", "correlativo": 102, "updatedAt": "..." },
    { "id": 2, "ruc": "20612690601", "tipoDocumento": "03", "serie": "B001", "correlativo": 101, "updatedAt": "..." }
  ]
}
```

`correlativo` es el último usado — es solo informativo, no lo mandes de vuelta al emitir (se
autogenera). `tipoDocumento` usa el mismo catálogo que `comprobante.tipo` (01 Factura, 03 Boleta,
07 NC, 08 ND, 09 Guía).

Si necesitas **crear** una serie nueva desde tu plugin (no solo leerlas), eso hoy solo se puede
hacer desde el dashboard — avísame si lo necesitas y lo habilitamos también por token.

---

## 6. Resultados asíncronos (Comunicación de Baja y Guía)

SUNAT no resuelve estos dos tipos al instante — la respuesta inicial trae un `ticket` con estado
`PENDIENTE`. Hay dos formas de enterarte del resultado final:

### Opción recomendada: Webhooks (push)
Configura una URL de webhook (ver sección 7) y espera el evento `documento.actualizado`. No
necesitas preguntar nada — el sistema te avisa apenas SUNAT resuelve el ticket (ya sea porque el
poller interno lo revisó automáticamente, o porque alguien lo consultó manualmente).

### Opción alternativa: Polling manual
El endpoint `POST /api/comprobantes/acciones` con `{ "docId": ..., "action": "consultar_ticket" }`
existe, pero usa sesión de dashboard (cookie), no Bearer Token — no está pensado para llamarse
desde una integración externa como WHMCS. Si de verdad lo necesitas sin webhooks, coordina conmigo
para exponer una versión con Bearer Token.

---

## 7. Webhooks — Notificaciones push

Configúralo en: **Dashboard → Empresas → tu RUC → pestaña Credenciales API → sección Webhooks.**

### Eventos que vas a recibir

| Evento | Cuándo se dispara | ¿Resultado final? |
|---|---|---|
| `documento.emitido` | Al emitir Factura/Boleta/NC/ND | Sí (síncrono) |
| `baja.enviada` | Al enviar una Comunicación de Baja | No — queda `PENDIENTE` |
| `guia.emitida` | Al enviar una Guía de Remisión | A veces (si SUNAT resolvió rápido) |
| `documento.actualizado` | Cuando una Baja/Guía `PENDIENTE` se resuelve | **Sí — este es el que te da el resultado final de los dos anteriores** |

### Payload que recibe tu endpoint

```json
{
  "evento": "documento.actualizado",
  "ruc": "20612690601",
  "data": {
    "tipoDocumento": "09",
    "serie": "T001",
    "correlativo": "00000113",
    "estado": "ACEPTADO",
    "descriptionSunat": "Guía procesada y aceptada correctamente.",
    "cdrUrl": "/data/cdr/20612690601/R-20612690601-09-T001-00000113.zip",
    "pdfUrl": "/data/pdf/20612690601/20612690601-09-T001-00000113.pdf",
    "ticket": "d396e996-0a76-4bf4-9be2-9701f728cb00"
  },
  "timestamp": "2026-07-05T01:16:53.820Z"
}
```

`estado` puede ser `PROCESADO`, `PENDIENTE`, `ACEPTADO` o `RECHAZADO` según el evento.

### Headers que recibe tu endpoint

```
Content-Type: application/json
X-Redeperu-Event: documento.actualizado
X-Redeperu-Signature: sha256=<hmac hexadecimal>
```

### Verificar la firma (obligatorio en producción)

La firma es un HMAC-SHA256 del cuerpo crudo (raw body, antes de parsear JSON) usando el secreto
de esa empresa. Ejemplo en PHP (para un plugin de WordPress):

```php
<?php
$rawBody = file_get_contents('php://input');
$firmaRecibida = $_SERVER['HTTP_X_REDEPERU_SIGNATURE'] ?? '';
$secreto = 'EL_SECRETO_DE_ESTA_EMPRESA'; // el que revelaste en el dashboard

$firmaEsperada = 'sha256=' . hash_hmac('sha256', $rawBody, $secreto);

if (!hash_equals($firmaEsperada, $firmaRecibida)) {
    http_response_code(401);
    exit('Firma inválida');
}

$payload = json_decode($rawBody, true);
// ... procesar $payload['evento'] y $payload['data'] ...

http_response_code(200);
echo 'ok';
```

Para WHMCS (PHP también, mismo principio — solo cambia dónde enganchas el endpoint, ej. un
archivo custom en `modules/addons/tu_modulo/webhook.php`).

### Comportamiento de entrega

- Hasta 3 intentos: inmediato, +2s, +6s. Timeout de 8s por intento.
- Se considera entregado si tu servidor responde **2xx**. Cualquier otro código o timeout cuenta
  como fallo.
- **No hay cola de reintentos persistente** — si tu servidor estuvo caído más tiempo que la
  ventana de reintentos, no vas a recibir ese evento de nuevo automáticamente. Para Baja/Guía
  igual puedes recuperar el estado más tarde consultando el documento desde el dashboard; para
  Factura/Boleta/NC/ND el resultado ya viene en la respuesta síncrona del propio
  `POST /api/comprobantes/enviar`, así que no dependes solo del webhook para esos.

---

## 8. Manejo de errores (aplica a `/enviar` y `/guia`)

| Código | Significado | Qué hacer |
|---|---|---|
| `401` | Token ausente o inválido | No reintentes con el mismo token — verifica que no se haya regenerado desde el dashboard |
| `402` | Límite de documentos del plan alcanzado | Mostrarle al usuario que debe ampliar su plan; el mensaje trae el detalle |
| `429` | Más de 60 solicitudes/minuto con ese token | Respeta el header `Retry-After` (segundos) antes de reintentar |
| `422` | Solo en Comunicación de Baja (`RA`): SUNAT la rechazó de plano, sin asignar ticket (ej. fecha de emisión inválida) | El `mensaje` trae el detalle real de SUNAT. No se creó ningún registro — no la confundas con una baja "enviada" |
| `500` | Error interno (SUNAT caída, datos mal formados, error de firma, etc.) | Revisa el campo `mensaje` — no reintentes ciegamente, podrías duplicar el envío |

Todas las respuestas de error siguen el formato:
```json
{ "estado": "ERROR", "mensaje": "..." }
```

---

## 9. Resumen rápido para el flujo típico de WHMCS

1. Al configurar el módulo: pedir **URL base**, **correo y contraseña** del usuario. Llamar
   `POST /api/auth/connect/empresas`, mostrar el selector de empresas, y con la elegida llamar
   `POST /api/auth/connect/token` para obtener y guardar el **Token API** (sección 2). Después,
   llamar `GET /api/companies/{ruc}/series` para poblar un selector de series por tipo de
   documento (factura/boleta), y guardarlo en la config del módulo.
2. Al generar/pagar una factura en WHMCS: mapear `tblinvoices` + `tblinvoiceitems` al payload de
   la sección 4.1 (tipo `01` o `03` según si el cliente tiene RUC o no) y hacer
   `POST /api/comprobantes/enviar`.
3. Guardar `xmlUrl`, `cdrUrl`, `pdfUrl` de la respuesta en la propia factura de WHMCS (como
   adjunto o campo custom) para que el cliente pueda descargarlos.
4. (Opcional pero recomendado) Configurar el webhook una sola vez desde el dashboard apuntando a
   un endpoint de tu módulo, para enterarte de cualquier Nota de Crédito/anulación que se procese
   después sin tener que hacer polling.
5. Manejar los códigos de error de la sección 8 con mensajes claros para el administrador de
   WHMCS (especialmente 402 y 429, que no son errores de SUNAT sino límites del propio sistema).

---

## 10. Dónde ver todo esto en vivo

El dashboard tiene una página de documentación interactiva en **`/api-docs`** con los mismos
payloads de ejemplo (copiables con un clic) y el estado en tiempo real de las conexiones a SUNAT
(útil para que tu módulo, o tú mismo al debuggear, sepan si un fallo es de SUNAT o del propio
sistema).
