# Fintech Solutions S.A. — Backend API

> **Asignatura:** Desarrollo Backend (IF201IINF)  
> **Institución:** Instituto Profesional San Sebastián  

---

## 👥 Integrantes del Grupo

| Nombre | Apellido |
|--------|----------|
| _Ricardo_ | _Fadus_ |

---

## 🔗 Repositorio GitHub

[https://github.com/Rfadusz/eva2_fadus_ricardo_project]
---

## 📁 Estructura del Proyecto

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── HealthController.php      # Sprint 0 — endpoint de observabilidad
│   │       └── ClientController.php      # Sprint 1 — CRUD de clientes
│   └── Models/
│       └── Client.php                    # Modelo Eloquent
├── database/
│   ├── migrations/
│   │   └── ..._create_clients_table.php  # Migración tabla clients
│   └── seeders/
│       └── ClientSeeder.php              # Datos iniciales
├── routes/
│   └── api.php                           # Rutas de la API
├── tests/
│   └── Feature/
│       ├── HealthEndpointTest.php        # Sprint 0
│       └── ClientCrudTest.php            # Sprint 1
├── docker/
│   ├── nginx/default.conf
│   └── php/dockerfile
├── docker-compose.yaml
└── .env
```

---

## ⚙️ Requisitos Previos

- Docker Desktop instalado y en ejecución
- Git instalado
- Puerto **8080** disponible en el equipo
- Puerto **3307** disponible (MySQL mapeado)

> No se requiere PHP ni Composer instalados localmente. Todo corre dentro de Docker.

---

## 🚀 Cómo Ejecutar el Proyecto

### 1. Clonar el repositorio

```bash
git clone https://github.com/Rfadusz/eva2_fadus_ricardo_project
cd eva2_fadus_ricardo_project
```

### 2. Ingresar a la carpeta del proyecto Laravel

```bash
cd backend
```

### 3. Copiar variables de entorno

```bash
cp .env.example .env
```

Asegurarse de que el `.env` tenga:

```dotenv
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=fintech
DB_USERNAME=fintech_user
DB_PASSWORD=fintech_pass
```

### 4. Levantar los contenedores Docker

```bash
docker compose up -d --build
```

Verificar que los tres servicios estén corriendo:

```bash
docker compose ps
```

Deben aparecer `fintech_app`, `fintech_web` y `fintech_db` con estado `Up`.

### 5. Instalar dependencias y generar clave

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

### 6. Ejecutar las migraciones

```bash
docker compose exec app php artisan migrate
```

### 7. (Opcional) Poblar con datos de prueba

```bash
docker compose exec app php artisan db:seed
```

### 8. Verificar que la API responde

```bash
curl http://localhost:8080/api/health
```

Respuesta esperada:
```json
{"status":"online","version":"1.0.0","environment":"docker"}
```

---

## 🗄️ Ejecutar Migraciones

```bash
# Ejecutar todas las migraciones pendientes
docker compose exec app php artisan migrate

# Ver estado de migraciones
docker compose exec app php artisan migrate:status

# Revertir y volver a ejecutar (⚠️ borra datos)
docker compose exec app php artisan migrate:fresh --seed
```

---

## 🧪 Ejecutar las Pruebas

```bash
# Todas las pruebas
docker compose exec app php artisan test

# Solo las pruebas del CRUD de clientes
docker compose exec app php artisan test --filter ClientCrudTest

# Solo las pruebas del Sprint 0
docker compose exec app php artisan test --filter HealthEndpointTest
```

---

## 📡 Endpoints Disponibles

Base URL: `http://localhost:8080`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/health` | Estado del servicio |
| GET | `/api/v1/clients` | Listar todos los clientes |
| POST | `/api/v1/clients` | Crear nuevo cliente |
| GET | `/api/v1/clients/search?q=texto` | Buscar clientes |
| GET | `/api/v1/clients/{id}` | Obtener cliente por ID |
| PUT | `/api/v1/clients/{id}` | Actualizar cliente |
| DELETE | `/api/v1/clients/{id}` | Eliminar cliente |

### Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa |
| 201 | Recurso creado |
| 400 | Solicitud incorrecta (ej: falta parámetro) |
| 404 | Recurso no encontrado |
| 409 | Conflicto (ej: email duplicado) |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## 📋 Colección Postman

El archivo `postman/EVA2_Fintech_Clients.postman_collection.json` contiene todos los endpoints listos para importar en Postman.

**Importar:**
1. Abrir Postman
2. `File → Import`
3. Seleccionar el archivo JSON de la carpeta `postman/`
4. Configurar la variable `base_url = http://localhost:8080`

---

## 📝 Historial de Evaluaciones

### EVA 1 — Sprint 0: Arquitectura Base

**Objetivo:** Configurar la infraestructura base con Docker y crear el primer endpoint.

**Entregables completados:**
- Proyecto Laravel 11 corriendo en Docker (Nginx + PHP-FPM + MySQL)
- `GET /api/health` → retorna estado del servicio en JSON
- Modelo `Client` con propiedades `fillable`
- Migración para la tabla `clients` (unique en `email`)
- Seeder con datos de prueba
- Prueba automática `HealthEndpointTest`
- Primer commit al repositorio con historial de cambios

---

### EVA 2 — Sprint 1: El Contrato de Datos

**Objetivo:** Implementar el CRUD completo de clientes con validaciones y manejo de errores.

**Entregables completados:**
- `ClientController` con 6 métodos: `index`, `store`, `show`, `update`, `destroy`, `search`
- Validaciones en `store` y `update`: campos obligatorios, formato email, unicidad, teléfono, edad ≥ 18 años
- Manejo de errores con códigos HTTP precisos: 200, 201, 400, 404, 409, 422, 500
- Rutas versionadas bajo `/api/v1/`
- Pruebas de integración en `ClientCrudTest` (11 casos de prueba)
- Colección Postman con 9 requests incluyendo casos de éxito y error
- Video demostrativo subido a OneDrive

---

## 🛑 Comandos Útiles

```bash
# Ver logs del contenedor PHP
docker compose logs app

# Ver logs en tiempo real
docker compose logs -f app

# Abrir terminal en el contenedor
docker compose exec app bash

# Ver rutas registradas
docker compose exec app php artisan route:list

# Limpiar caché
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan cache:clear

# Detener contenedores
docker compose down
```

---

## 🔧 Stack Técnico

| Tecnología | Versión | Uso |
|------------|---------|-----|
| Laravel | 11.x | Framework PHP |
| PHP | 8.2 | Lenguaje backend |
| MySQL | 8.0 | Base de datos |
| Nginx | 1.27 | Servidor web |
| Docker | Latest | Contenedorización |
| Composer | 2.x | Gestor de dependencias PHP |

---

**Última actualización:** Mayo 2026  
**Versión de API:** 1.0
