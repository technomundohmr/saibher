# Saibher Web Services

Gateway API de **lectura y escritura** para el tipo de contenido *Artículos* de
saibher.com, pensado para integraciones externas (apps, CRMs, automatizaciones).
El módulo es autocontenido: no depende de `jsonapi` ni de `simple_oauth`.

**Endpoints (formato JSON):**

| Método | Ruta                    | Permiso requerido                | Respuesta |
| ------ | ----------------------- | -------------------------------- | --------- |
| GET    | `/api/v1/articles`      | `access saibher web services`    | 200       |
| GET    | `/api/v1/articles/{article_key}` | `access saibher web services` | 200       |
| POST   | `/api/v1/articles`      | `access saibher web services write` | 201    |
| PATCH  | `/api/v1/articles/{article_key}` | `access saibher web services write` | 200 |

`article_key` acepta un **nid** (p. ej. `212`) o un **UUID** (p. ej.
`3082ff4c-7f8c-4d71-a91f-1a14f94406fe`).

---

## Autenticación

Cada petición debe enviar el header:

```
Authorization: Saibher {keyId}.{secret}
```

El *credential* completo (`keyId.secret`) se entrega **una sola vez** al
crear o rotar la clave. Solo se almacena en la base de datos un hash SHA‑256
con sal del secret, de modo que ni siquiera un administrador de la base puede
recuperar el valor original.

**Comportamiento de errores de autenticación:**

| Situación                             | Resultado                                                                 |
| ------------------------------------- | ------------------------------------------------------------------------- |
| Sin header `Authorization`            | `401 Unauthorized` + header `WWW-Authenticate: Saibher`                  |
| Esquema distinto (`Basic`, `Bearer`…) | `401 Unauthorized`                                                        |
| Secret inválido, expirado o revocado  | `401 Unauthorized`                                                        |
| Clave válida pero sin permiso         | `403 Forbidden`                                                           |

Una API key está vinculada a una cuenta de usuario de Drupal: la petición se
autentica **como esa cuenta**, heredando sus roles y permisos. La separación
de responsabilidades se logra con roles dedicados:

- `access saibher web services` → permite leer (GET).
- `access saibher web services write` → permite crear y actualizar (POST/PATCH).
- `administer saibher web services` → gestión de la configuración y de las
  claves (restringido).

> En el entorno de desarrollo se crearon los roles de ejemplo `saibher_api_consumer`
> (lectura + escritura) y `saibher_api_reader` (solo lectura), y los usuarios
> `api_svc` (uid 774) y `api_reader` (uid 775).

---

## Instalación y configuración

1. Coloca el módulo en `web/modules/custom/saibher_web_services/` (composer
   autoload o volcado simple).
2. `drush en saibher_web_services`
3. El módulo instala la configuración por defecto
   (`saibher_web_services.settings`).
4. Ajusta la configuración en
   **Administración → Configuración → Servicios web → Saibher Web Services**
   (`/admin/config/services/saibher-web-services`):

   - **Formatos de texto permitidos** (`allowed_formats`): en esta instalación
     se permiten `basic_html` y `restricted_html`. Un payload con otro formato
     recibe `422` (p. ej. `full_html`).
   - **Publicar por defecto** (`article_status_default`): estado de los
     artículos creados cuando el payload no trae `status`.
   - **Solo publicados en listados** (`only_published_in_lists`): el GET de
     lista oculta borradores salvo que el cliente filtre por `status`.
   - **Tamaño máximo de imagen** (`image_max_bytes`): límite en bytes de las
     imágenes remotas descargadas (por defecto 10 MB).

---

## Gestión de API keys

### Panel de administración

En `/admin/config/services/saibher-web-services/keys` se pueden listar,
crear, editar, **rotar** y **revocar** claves. Al crear o rotar, el módulo
redirige a una página que muestra el secret **una única vez** (guardado en
tempstore privada y borrado al mostrarse).

### Drush (ideal para CI/pipelines y generación sin intervención de código)

```bash
# Crear (la credencial se imprime una sola vez)
drush saibher:api-key:add "Sistema de facturación - producción" --uid=774 --days=3650

# Listar
drush saibher:api-key:list

# Revocar (inmediato; las llamadas pasan a 401)
drush saibher:api-key:revoke <keyId>

# Rotar (nuevo secret, el anterior deja de valer)
drush saibher:api-key:rotate <keyId>
```

Ejemplo con salida para automatización:

```bash
drush saibher:api-key:add "CI build" --uid=774 --days=90 \
  | grep -oE '[0-9a-f]{24}\.[0-9a-f]+'   # extrae keyId.secret
```

La cuenta vinculada debe existir y estar activa; la creación de claves
**no requiere desplegar código** (todas las opciones son config y Drush).

---

## Uso de la API

### Listar artículos

```bash
curl -s -H "Authorization: Saibher $CRED" \
  "https://saibher.ddev.site:8443/api/v1/articles?limit=10&offset=0&sort=created&dir=desc"
```

Parámetros de query:

| Parámetro | Valores              | Descripción                                   |
| --------- | -------------------- | --------------------------------------------- |
| `limit`   | 1–50 (por defecto 10) | Tamaño de página                             |
| `offset`  | ≥ 0                   | Desplazamiento para paginar                  |
| `title`   | texto                | Filtro por substring del título (LIKE)        |
| `status`  | `0` / `1`            | Filtro por estado (anula el ajuste del sitio) |
| `sort`    | `nid`, `title`, `created`, `changed`, `status` | Orden  |
| `dir`     | `asc` / `desc`       | Dirección                                    |

Estructura de la lista (los artículos van **sin** `body`, con un `summary`
de hasta 255 caracteres):

```json
{
  "data": [
    {
      "id": 212,
      "uuid": "3082ff4c-…",
      "type": "article",
      "title": "Cómo Descargar y Usar el Módulo Admin Toolbar…",
      "status": true,
      "created": 1717995953,
      "changed": 1717996220,
      "author": { "id": 1, "name": "saibher_developer" },
      "url": "https://saibher.ddev.site:8443/blog/drupal/…",
      "alias": "/blog/drupal/…",
      "category": [{ "id": 5, "name": "Drupal" }],
      "tags": [{ "id": 10, "name": "Drupal" }],
      "image": { "fid": 230, "url": "https://…/admin_toolbar.jpg", "alt": "…" },
      "summary": "…"
    }
  ],
  "meta": { "total": 41, "limit": 10, "offset": 0, "sort": "created", "dir": "desc", "language": "es" }
}
```

### Obtener un artículo

```bash
curl -s -H "Authorization: Saibher $CRED" \
  "https://saibher.ddev.site:8443/api/v1/articles/212"
```

Igual que la lista, pero incluye el campo `body`:

```json
"body": { "value": "<p>…</p>", "summary": "…", "format": "basic_html" }
```

### Crear un artículo

```bash
curl -s -X POST -H "Authorization: Saibher $CRED" -H "Content-Type: application/json" \
  "https://saibher.ddev.site:8443/api/v1/articles" -d '{
    "title": "Título del artículo",
    "body": {
      "value": "<p>Contenido HTML.</p>",
      "summary": "Resumen opcional.",
      "format": "basic_html"
    },
    "status": true,
    "category": [ { "id": 5 } ],
    "tags": [ { "id": 10 }, { "name": "Tag nuevo (auto-creado)" } ],
    "image": { "url": "https://ejemplo.com/foto.jpg", "alt": "Texto alternativo" }
  }'
```

- Respuesta `201 Created` con el artículo normalizado y header `Location`.
- **Campos escriturables:** `title`, `body`, `status`, `category`
  (máquina `field_category`, vocabulario `article_category`, solo términos ya
  existentes), `tags` (máquina `field_tags`, vocabulario `tags`, con
  auto-creación por nombre) e `image`.
- Se aceptan tanto los nombres externos (`category`, `tags`) como los nombres
  de máquina (`field_category`, `field_tags`).
- **Campos ignorados / bloqueados:** `uid`, `author`, `created`, `changed`,
  revisiones y cualquier campo desconocido se registran en el log y se
  descartan. El autor y las fechas son siempre los de la cuenta vinculada.

### Actualizar un artículo (PATCH, parcial)

```bash
curl -s -X PATCH -H "Authorization: Saibher $CRED" -H "Content-Type: application/json" \
  "https://saibher.ddev.site:8443/api/v1/articles/212" -d '{
    "title": "Nuevo título",
    "tags": [ { "id": 10 } ]
  }'
```

- Los campos ausentes no se modifican (semántica PATCH).
- Cada actualización crea una **revisión nueva** del nodo con log
  «Actualizado a través de la API de Saibher Web Services.».

---

## Responses de error

Formato homogéneo:

```json
{
  "error": {
    "status": 401,
    "code": "unauthorized",
    "message": "Se requiere una API key válida. Envía el header \"Authorization: Saibher {clave}\".",
    "fields": { "title": "El campo \"title\" es obligatorio." }
  }
}
```

| Código HTTP | `code`               | Cuándo                                                        |
| ----------- | -------------------- | ------------------------------------------------------------- |
| 401         | `unauthorized`       | Falta credencial, es inválida, expiró o fue revocada.          |
| 403         | `forbidden`          | Credencial válida pero sin permisos para la operación.         |
| 404         | `not_found`          | El artículo (nid/UUID) no existe.                              |
| 405         | *(Symfony estándar)* | Método no soportado (p. ej. `PUT`).                            |
| 415         | `request_error`      | Content-Type no es `application/json`.                         |
| 422         | `validation_failed`  | Payload válido sintácticamente pero con errores (`fields`).    |
| 500         | `internal_error`     | Error interno (registrado en watchdog, sin detalles al cliente). |

---

## Seguridad y recomendaciones

- **No se usa IP restriction**: la autenticación depende únicamente de la API
  key. Evita portar claves por red: usa TLS (HTTPS) en producción y en el
  header `Authorization` (nunca en la URL).
- **Rotación y revocación sin código**: las claves se rotan/revocan por Drush
  o por el panel; el impacto es inmediato (`401`).
- **Permisos mínimos**: crea una cuenta de servicio dedicada por integración y
  asígnale solo el rol mínimo (lectura o lectura+escritura). Para crear
  artículos necesita además los permisos de contenido estándar de Drupal
  (p. ej. `create article content`). Revisa la cuenta de servicio igual que a
  un editor.
- **Rate limiting / WAF**: se recomienda proteger `/api/v1/*` con limitación
  de tasa a nivel de servidor proxy (p. ej. Nginx `limit_req`);
  `drush saibher:api-key:list` muestra el último uso de cada clave para auditar.
- **Auditoría**: el módulo registra en watchdog cada creación/actualización
  (`Article @nid created via API by user @uid.`) y cada intento con
  credencial inválida.
- **Formatos de texto**: solo los formatos configurados son aceptados;
  revisa los roles que pueden usar `restricted_html`/`full_html` en tu sitio.
- **Imágenes remotas**: verificación TLS habilitada por defecto; el tamaño se
  limita (`image_max_bytes`). El archivo se registra como uso (`file.usage`).
- **Cache**: los responses del API llevan contexto de usuario, por lo que no
  se sirven desde page cache anónimo; verifica la caché de tu firewall/proxy.

---

## Postman

La colección se encuentra en
`postman/saibher-web-services.postman_collection.json`. Define las variables
`baseUrl` y `credential` y ejecuta los requests incluidos (listar, obtener,
crear, actualizar y casos de error).

## Estructura del módulo

```
saibher_web_services/
├── config/install/saibher_web_services.settings.yml
├── config/schema/saibher_web_services.schema.yml
├── css/admin.css
├── postman/saibher-web-services.postman_collection.json
├── src/
│   ├── Authentication/Provider/ApiKeyAuth.php      # scheme "Saibher" + challenge 401
│   ├── Commands/SaibherApiKeyCommands.php          # drush saibher:api-key:*
│   ├── Controller/ArticleApiController.php         # endpoints HTTP
│   ├── Controller/ApiKeyRevealController.php       # muestra el secret una vez
│   ├── Entity/SaibherApiKey.php                    # entidad config que guarda la clave
│   ├── EventSubscriber/ApiExceptionSubscriber.php  # errores JSON en /api/*
│   ├── Form/ApiKeyForm.php                         # crear/editar/rotar por UI
│   ├── Form/SettingsForm.php                       # opciones del módulo
│   └── Service/
│       ├── ApiKeyService.php                       # hash, validación, rotación
│       ├── ArticleApiService.php                   # lógica de negocio de artículos
│       └── ApiValidationException.php
├── saibher_web_services.info.yml
├── saibher_web_services.permissions.yml
├── saibher_web_services.routing.yml
├── saibher_web_services.services.yml
└── drush.services.yml
```