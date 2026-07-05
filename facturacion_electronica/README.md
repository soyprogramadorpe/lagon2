# Facturación Electrónica — WHMCS Addon Module

**Versión:** 1.0.0 | **Autor:** Cultura Interactiva | **WHMCS 8.x · Template Lagon · PHP 8.0+**

---

## Estructura

```
modules/addons/facturacion_electronica/
│
├── facturacion_electronica.php   ← Archivo principal (config, activación, panel admin)
├── hooks/
│   └── checkout.php              ← Toggle en checkout, fee, hooks de sesión
├── ajax/
│   └── fee.php                   ← Endpoint AJAX: calcula IGV y devuelve JSON
├── lang/
│   ├── spanish.php
│   └── english.php
├── lib/
│   ├── Api/FacturacionClient.php ← HTTP client base (cURL)
│   └── Providers/BaseProvider.php← Factory + NubefactProvider stub (Fase 2)
├── storage/                      ← PDFs/XMLs descargados (Fase 2)
└── README.md
```

---

## Instalación

1. Copiar carpeta a `/modules/addons/facturacion_electronica/`
2. `Setup → Addon Modules → Facturación Electrónica → Activate`
3. `Configure` y ajustar tasa IGV, textos, y proveedor API (Fase 2)

---

## Cómo funciona (Fase 1)

```
Toggle ON  →  POST AJAX a ajax/fee.php
           →  Overlay + spinner
           →  Guarda $_SESSION['fe_quiere_factura']
           →  Calcula IGV sobre subtotal del carrito
           →  Devuelve JSON con totales formateados
           →  JS actualiza DOM del resumen (sin reload)

CalcCartTotals hook  →  Lee sesión  →  Agrega fee a la invoice de WHMCS
```

---

## Tablas creadas al activar

| Tabla | Propósito |
|---|---|
| `mod_fe_facturas` | Registro de comprobantes emitidos (Fase 2) |
| `mod_fe_clientes_fiscales` | RUC/DNI y datos fiscales del cliente |

---

## Roadmap Fase 2

- [ ] Formulario RUC/razón social en checkout
- [ ] Hook `InvoicePaid` → emisión automática
- [ ] NubefactProvider completo
- [ ] EfactProvider / SunatDirect
- [ ] Panel admin con listado y filtros
- [ ] Descarga PDF/XML y reenvío manual
