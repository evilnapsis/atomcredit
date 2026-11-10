# ⚡ AtomCredit (Legobox 5)

Sistema web para administración y seguimiento de préstamos financieros y microcréditos, reconstruido sobre la arquitectura **Legobox 5**.

---

## 🚀 Stack Tecnológico

* **Backend:** PHP 8.2+
* **Base de Datos:** MySQL 8.0+ / MariaDB con PDO y Prepared Statements (UTF-8 mb4)
* **Enrutamiento:** FastRoute (rutas limpias y desacopladas)
* **Motor de Vistas:** Twig 3
* **Frontend:** CoreUI v5 / Bootstrap 5, Bootstrap Icons y SweetAlert2
* **Diseño y Tipografía:** Outfit Sans-serif con paleta Deep Indigo (`#1e1b4b`) y acentos `#6366f1`

---

## 📁 Estructura del Proyecto

```text
atomcredit/
├── assets/                       # Íconos y fuentes locales
├── core/
│   ├── autoload.php              # Carga de dependencias y componentes centrales
│   ├── controller/               # Motor Legobox (Database, ViewEngine, Session, Req, Response)
│   └── app/
│       ├── autoload.php          # Autoloader PSR-4 para controladores, servicios y modelos
│       ├── routes.php            # Tabla declarativa de rutas FastRoute
│       ├── controller/           # Controladores de la aplicación
│       ├── model/                # Modelos de datos Active Record (PDO Prepared Statements)
│       └── service/              # Servicios de autenticación y lógica de negocio
├── css/                          # Estilos personalizados y CoreUI
├── js/                           # jQuery y scripts base
├── public/                       # Plantillas y vistas Twig (.html.twig)
│   ├── layouts/
│   │   └── main.html.twig        # Layout maestro (Sidebar índigo, SweetAlert2, CSRF)
│   ├── auth/
│   │   └── login.html.twig       # Pantalla de inicio de sesión
│   ├── home/
│   │   └── index.html.twig       # Dashboard y resumen de cartera
│   ├── loans/                    # Listado, originación y detalle de amortización
│   ├── clients/                  # Catálogo de prestatarios
│   ├── loan_products/            # Líneas y productos de crédito
│   ├── settings/                 # Parámetros generales y tasas de mora
│   └── users/                    # Cuentas de usuario y perfiles
├── vendors/                      # Librerías frontend (CoreUI, Simplebar, DataTables, SweetAlert2)
├── vendor/                       # Dependencias Composer (Twig, FastRoute)
├── .htaccess                     # Reescritura Apache hacia index.php
├── index.php                     # Front Controller con protección CSRF
├── schema.sql                    # Esquema DDL canónico con comentarios y ENUMs
├── demo_data.sql                 # Datos demo distribuidos en los últimos 30 días
└── README.md                     # Documentación general
```

---

## 🗄️ Instalación de la Base de Datos

1. Crear la base de datos e importar la estructura base:
   ```bash
   mysql -u root < schema.sql
   ```
2. *(Opcional)* Cargar el conjunto de datos de prueba para visualizar estadísticas en el Dashboard:
   ```bash
   mysql -u root atomcredit < demo_data.sql
   ```

---

## 🔐 Credenciales de Acceso por Defecto

* **URL:** `http://localhost/atomcredit/login`
* **Usuario:** `admin`
* **Contraseña:** `admin`
