# 🔐 ATLAS Auth Backend

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12.0">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/API-REST-00D9FF?style=for-the-badge" alt="REST API">
  <img src="https://img.shields.io/badge/Auth-OAuth2-4285F4?style=for-the-badge&logo=google&logoColor=white" alt="OAuth2">
</p>

<p align="center">
  <img src="https://img.shields.io/github/license/Hannd15/atlas-auth-backend?style=flat-square" alt="License">
  <img src="https://img.shields.io/github/last-commit/Hannd15/atlas-auth-backend?style=flat-square" alt="Last Commit">
  <img src="https://img.shields.io/github/workflow/status/Hannd15/atlas-auth-backend/tests?style=flat-square" alt="Tests">
</p>

## 📋 Descripción

**ATLAS Auth Backend** es el módulo de autenticación y autorización del sistema ATLAS, construido sobre **Laravel Framework v12.0**. Este backend constituye la **capa de seguridad principal** del ecosistema ATLAS, proporcionando servicios robustos de autenticación, autorización granular y gestión de usuarios con arquitectura modular y escalable.

### 🎯 Características Principales

- 🔑 **Autenticación Multi-Canal** - Soporte para web, API y OAuth2 (Google)
- 🛡️ **Autorización Granular** - Sistema de roles y permisos con Spatie Laravel Permission
- 🔒 **Seguridad Reforzada** - Laravel Sanctum para gestión de tokens y sesiones
- 📚 **Documentación Automática** - API REST documentada con OpenAPI/Swagger
- 🧪 **Calidad Asegurada** - Suite completa de tests con PHPUnit
- 🎨 **Código Estandarizado** - Laravel Pint para consistencia de estilo

## 🏗️ Arquitectura

### Patrón MVC Extendido con Capas Adicionales

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   HTTP Layer    │ -> │  Service Layer  │ -> │ Repository Layer │
│   Controllers   │    │    Business     │    │   Data Access   │
│   Middleware    │    │     Logic       │    │   Persistence   │
│   Requests      │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         |                       |                       |
         v                       v                       v
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Auth & Authz   │    │     Events      │    │     Models      │
│    Sanctum      │    │   Listeners     │    │   Eloquent ORM  │
│    Spatie       │    │     Jobs        │    │   Factories     │
│   Socialite     │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### 🔧 Componentes Principales

#### **Capa HTTP**
- **Controladores**: Gestión de solicitudes API con delegación a servicios
- **Middleware**: Validación de autenticación, control de roles y manejo de sesiones
- **Form Requests**: Validación de entrada y autorización de recursos

#### **Autenticación y Autorización**
- **Laravel Sanctum**: Gestión de tokens SPA y API
- **Spatie Laravel Permission**: Sistema granular de roles y permisos
- **Laravel Socialite**: OAuth2 con Google y otros proveedores

#### **Modelos y Persistencia**
- **Eloquent ORM**: Representación de entidades del dominio
- **Repositorios**: Abstracción de la capa de persistencia
- **Migraciones**: Esquema versionado de base de datos
- **Factories & Seeders**: Datos de prueba e inicialización

## 🚀 Inicio Rápido

### Prerequisitos

- PHP 8.2 o superior
- Composer 2.x
- Node.js 18+ y npm
- Base de datos (MySQL/PostgreSQL/SQLite)

### Instalación

```bash
# Clonar el repositorio
git clone https://github.com/Hannd15/atlas-auth-backend.git
cd atlas-auth-backend

# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install

# Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos
php artisan migrate --seed

# Instalar Passport (si se usa)
php artisan passport:install

# Compilar assets
npm run build
```

### Configuración OAuth2

```bash
# Configurar Google OAuth
# Añadir en .env:
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

## 📖 Documentación API

### Swagger/OpenAPI

La documentación interactiva de la API está disponible en:

```
http://localhost:8000/api/documentation
```

## 📁 Estructura del Proyecto

```
atlas-auth-backend/
├── app/
│   ├── Http/Controllers/        # Controladores
│   ├── Models/                  # Modelos Eloquent
│   └── Providers/               # Service Providers
├── config/                      # Configuraciones
├── database/
│   ├── migrations/              # Migraciones
│   ├── seeders/                 # Seeders
│   └── factories/               # Model Factories
├── routes/                      # Rutas
├── tests/                       # Tests automatizados
└── storage/api-docs/            # Documentación Swagger
```

## 🛡️ Seguridad

- **Tokens JWT** con Laravel Sanctum
- **Rate Limiting** en endpoints críticos
- **CORS** configurado para frontend
- **Validación** robusta en todos los endpoints
- **Encriptación** de datos sensibles

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama de feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abrir Pull Request

---

<p align="center">
  Construido con ❤️ usando <a href="https://laravel.com">Laravel</a>
</p>
