# Saibher User Management

Registro de usuarios, página de perfil y **sistema de marketing de afiliados**
(referidos + comisiones) para el sitio de Saibher (Drupal 11, POS/ERP con
Commerce).

## Flujo del afiliado

1. Todo usuario registrado recibe un **código de afiliado único** de 8
   caracteres (`field_affiliate_code`, base field de `user`).
2. El afiliado comparte su **enlace** (`/r/CODIGO`) o el **código**.
3. Al entrar por el enlace — o con `?ref=CODIGO` en cualquier página — el
   sistema guarda el código del afiliado en la **cookie `saibher_ref`**
   (atribución de *primer contacto*: nunca se sobrescribe si ya existe).
4. Si esa persona se **registra**, se crea un registro de `affiliate_referral`
   que vincula al nuevo usuario con el afiliado que lo refirió.
5. Las comisiones se registran **manualmente** desde el panel administrativo
   (`/admin/saibher/affiliate-commissions`): monto, moneda, estado y notas se
   acreditan al afiliado correspondiente.

## Decisiones de arquitectura

Se **reutilizan** entidades de Drupal existentes:

- **`user`**: el afiliado es un usuario normal. El código de afiliado y los
  datos de perfil (nombre, teléfono, país, sitio web, negocio) se almacenan
  como *base fields* de la entidad user, de modo que funcionan igual en todo
  el sistema con campos, formularios y vistas estándar.

Se **crean dos nuevas entidades de contenido** (y no tablas crudas) para los
dos conceptos de dominio que Drupal no modela:

| Entidad                | Tabla                        | Para qué                                                                 |
| ---------------------- | ---------------------------- | ------------------------------------------------------------------------ |
| `affiliate_referral`   | `saibher_affiliate_referral` | Relación afiliado → persona registrada por su enlace (referido).         |
| `affiliate_commission` | `saibher_affiliate_commission` | Dinero ganado por el afiliado por un referido/pedido (manual o automático). |

**Por qué entidades de contenido en lugar de tablas SQL**: Drupal entrega
gratis el CRUD, el guardado con timestamps, el manejo de referencias a
usuarios/pedidos validado, la integración con Views, la paginación y la
convención de API de campos. Además es el mismo patrón ya usado en el módulo
`saibher_tracking`, manteniendo la coherencia de la arquitectura del sitio.

## Datos que guarda cada entidad

### `affiliate_referral`
- `referrer`: user referencia al afiliado (quien invita).
- `referred`: user referencia al nuevo usuario (quien se registra).
- `created` / `changed`.

Garantía: un referido solo se asocia **una vez** (no se re-asigna) y un usuario
no puede referirse a sí mismo.

### `affiliate_commission`
- `uid`: usuario que registró la comisión (auditoría).
- `referrer`: afiliado que gana la comisión.
- `referred`: cliente referido (opcional).
- `referral`: `affiliate_referral` que originó la comisión (opcional).
- `amount` + `currency`: monto exacto (decimal 16,2) y moneda ISO (COP por defecto).
- `status`: `pending`, `approved`, `paid` o `rejected`.
- `notes`: notas (obligatorios de facto en el alta manual).
- `created` / `changed`.

## Panel del afiliado (balance)

El perfil del usuario (`/mi-perfil`) incluye una **tabla funcional de balances**
renderizada con JavaScript puro (comportamiento `saibherProfileBalances` en
`js/saibher-profile.js`), sin dependencias externas. La tabla permite:

- **Filtro por mes** (`select`): "Todos" (histórico acumulado) o un mes concreto.
- **Totales** sobre las filas: referidos, compradores y ganado por moneda, con
  desglose de pendientes/aprobados/pagados.
- **Orden por columna**: Persona, Registrado, Órdenes y Ganado (clic alterna
  asc/desc, con indicador ↑/↓).
- **Paginación** (Anterior/Siguiente, página X de Y) e indicadores de carga y de
  vacío ("Aún no tienes referidos").
- **Privacidad**: cada fila muestra solo el **nombre y la inicial del apellido**
  (p. ej. "María G."), nunca el correo completo.
- El panel solo se renderiza con el permiso
  `saibher_user_management.view_own_affiliate_panel`; en el endpoint, el mismo
  permiso devuelve `403` sino se tiene.

Back-end del panel:

- **Endpoint JSON**: `GET /mi-perfil/afiliado/balances`
  - Parámetros: `month=YYYY-MM`, `page`, `limit` (10-100, default 20),
    `sort` (`joined`|`earned`|`orders`|`name`), `dir` (`asc`|`desc`).
  - Respuesta: `months[]`, `totals{referred_count, buyers_count, by_currency[]}`,
    `rows[]` (una fila por persona referida, incluindo quienes aún no han
    comprado) y `pagination{}`.
- **Servicio**: `saibher_user_management.ledger` (`AffiliateLedger`) — todos los
  totales se recalcularan desde los `affiliate_commission` en cada consulta, para
  garantizar consistencia. Las comisiones `rejected` se excluyen de ganancias.
- **Filtro por mes**: si se envía `month`, se filtran referidos **y** comisiones
  de ese mes; sin `month` se devuelve el histórico acumulado.

## Permisos

| Permiso | Para qué | Rol sugerido |
| ------- | -------- | ------------ |
| `saibher_user_management.view_own_profile` | Ver la propia página `/mi-perfil`. | authenticated |
| `saibher_user_management.edit_own_profile` | Editar los propios datos y subir foto. | authenticated |
| `saibher_user_management.view_any_profile` | Ver el perfil de otros usuarios. | administrator |
| `saibher_user_management.edit_any_profile` | Editar el perfil de otros usuarios. | administrator |
| `saibher_user_management.view_own_affiliate_panel` | Ver el propio panel/balance de afiliado. | authenticated |
| `saibher_user_management.view_any_affiliate_panel` | Ver el panel/balance de cualquier afiliado. | administrator |
| `saibher_user_management.manage_affiliate_commissions` | Alta/edición/borrado manual de comisiones y vista del listado admin. | administrator |
| `saibher_user_management.administer_affiliate_settings` | Cambiar tasa de comisión, moneda, estado inicial y duración de cookie. | administrator |

El rol **administrator** del sitio (`is_admin = true`) los tiene todos por
omisión. En un sitio multiusuario, `saibher_user_management.manage_affiliate_commissions` /
`saibher_user_management.administer_affiliate_settings` se asignarían a un rol
de contabilidad.

### Acceso a rutas
- `/r/{code}` y `/registro`: públicos.
- `/mi-perfil*`: según los permisos de perfil anteriores (una persona autenticada
  sin `saibher_user_management.view_own_profile` no puede ver su perfil).
- `/admin/saibher/*`: admin (requisitos propios + `_admin_route`).

## Comisiones manuales (administración)

- Listado + alta/edición/borrado en `/admin/saibher/affiliate-commissions`
  (entidad `affiliate_commission`, list builder con paginación y orden).
- Permite elegir afiliado, referido opcional, monto, moneda, estado y notas.

## Comisiones

El sistema de comisiones no depende de Commerce: cada comisión se registra
desde el formulario administrativo. El balance del afiliado (`/mi-perfil`) se
calcula exclusivamente desde las entidades `affiliate_referral` y
`affiliate_commission` — ninguna entidad externa.

## Configuración

`/admin/saibher/affiliate-settings`:
- `default_currency`: moneda de las comisiones (default `COP`).
- `default_status`: estado inicial de las comisiones (default `pending`).
- `cookie_lifetime`: duración de la cookie `saibher_ref` en días.

## Perfil de usuario

`/mi-perfil` reutiliza los datos existentes de `user` y sigue la referencia
visual provista:

1. **Encabezado**: foto de perfil (con carga por AJAX a `/mi-perfil/foto`,
   que valida que sea imagen y la asocia al campo `user_picture`), nombre,
   negocio y pista de ayuda.
2. **Tus datos**: correo, teléfono (con código de país), país (nombre legible)
   y sitio web + botón **Editar**.
3. **Tu enlace de afiliado**: enlace completo con botón **Copiar enlace** y el
   contenedor del balance de afiliado.
4. **Contraseña**: enlace al flujo estándar de cambio de contraseña de Drupal.

La fotografía también se puede gestionar desde `/mi-perfil/editar` (widget
`managed_file` en el formulario de edición).

## Notas técnicas

- Las *base fields* del usuario se instalan vía `hook_install` /
  `hook_entity_base_field_info`. Si ya existía una versión previa del módulo,
  las funciones `saibher_user_management_update_9002/3/4` migran el esquema.
- El módulo depende de `file` e `image` para la foto de perfil. El sistema de
  referidos y comisiones es independiente de Commerce (no hay dependencia alguna
  en el `info.yml` ni en el código).