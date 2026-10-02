# Análisis SEO / GEO y Plan de Posicionamiento — saibher.com

**Fecha:** 25 de septiembre de 2026
**Sitio analizado:** https://saibher.com
**Nota de método:** El diagnóstico se basa en datos observados directamente (código HTML de páginas en vivo, `robots.txt`, `sitemap.xml`, encabezados HTTP y medición real de carga en navegador controlado) y en investigación externa (búsquedas web, perfiles de la marca). Cuando un dato no pudo verificarse, se indica explícitamente como **ESTIMADO** o **NO VERIFICADO**. No se asumió ni inventó información.

---

## Tabla de contenidos

1. [Resumen ejecutivo](#resumen-ejecutivo)
2. [Fase 1 — Diagnóstico](#fase-1--diagnóstico)
   - 1.1 SEO técnico
   - 1.2 SEO de contenido
   - 1.3 Autoridad y presencia externa
   - 1.4 GEO / posicionamiento en IA generativa
   - 1.5 Competencia
3. [Fase 2 — Plan de acción](#fase-2--plan-de-acción)
   - 2.1 Quick wins a corto plazo
   - 2.2 Plan de contenido a mediano plazo
   - 2.3 Estrategia de autoridad / backlinks
   - 2.4 Estrategia específica de GEO
   - 2.5 Métricas de seguimiento
4. [Tareas — desglose de ejecución técnica](#tareas--desglose-de-ejecución-técnica)
   - Fase A: Quick wins técnicos (T-01 … T-16)
   - Fase B: Contenido (T-17 … T-24)
   - Fase C: Autoridad / backlinks (T-25 … T-32)
   - Fase D: GEO (T-33 … T-38)
   - Fase E: Métricas y seguimiento (T-39 … T-43)

---

## Resumen ejecutivo

**Saibher tiene un gran problema de base y una gran oportunidad.** El sitio es técnicamente sano (Drupal 11, HTTPS, móvil, CDN con HTTP/2 y Brotli), pero es **invisible** en Google y en los motores de IA: no aparece en ningún ranking relevante, en ningún listado ("mejores agencias de desarrollo web Colombia"), no tiene backlinks externos y su blog llevan más de un año sin publicar (41 artículos: 37 de 2023, 4 de 2024).

Los hallazgos críticos:

1. **Errores técnicos puntuales que cuestan visibilidad:** el `canonical` de la home apunta a una URL que hace redirección (`/home-2` → `/`); `http://` y `www` no redirigen a la versión canónica; no existe ningún dato estructurado (schema.org) en todo el sitio; 8 de las páginas principales carecen de H1.
2. **Velocidad deficiente:** LCP real medido de ~5,3 s (objetivo Google < 2,5 s), TTFB de ~3,7 s y una imagen del "hero" de 1,45 MB. El caché anónimo de página está desactivado.
3. **Contenido desconectado del mercado:** el blog habla a desarrolladores (tutoriales de Drupal/PHP) mientras los clientes reales buscan solución a "punto de venta", "ERP a medida", "cuánto cuesta una página web en Colombia". El diferenciador único de Saibher — **POS con licencia vitalicia sin mensualidad** — prácticamente no tiene páginas optimizadas.
4. **Autoridad casi nula:** perfil de enlaces de solo perfiles propios (Drupal.org, GitHub, LinkedIn). Sin Google Business Profile, sin directorios, sin menciones externas.

**Lo más importante por hacer (en orden):**
1. Convertir *quick wins* técnicos: canonical, redirecciones HTTPS/www, activar caché de página, optimizar la imagen del hero y habilitar JSON-LD (Organización + FAQPage).
2. Reactivar el blog con un calendario enfocado al comprador (POS, ERP a medida, precios, Drupal para pymes) con 2 publicaciones mensuales.
3. Crear/reclamar el **Google Business Profile** de Saibher y páginas de servicio locales (Bogotá + Villavicencio).
4. Montar la infraestructura GEO: schema.org en todo el sitio, respuestas directas tipo "¿qué es…?", autoría clara y páginas individuales del portafolio con casos de éxito.
5. Construir autoridad: perfil completo en Drupal.org, participaciones en la comunidad Drupal de Colombia, directorios de agencias (Clutch, GoodFirms, DesignRush) y LinkedIn Company.

---

# Fase 1 — Diagnóstico

## 1.1 SEO técnico

### 1.1.1 Stack y servidor

| Aspecto | Estado observado | Detalle |
|---|---|---|
| Plataforma | Drupal 11 | `X-Generator: Drupal 11`; módulos: metatag, schema_metatag, hreflang, xmlsitemap, redirect, google_tag (GA4), yoast_seo, easy_breadcrumb, robotstxt, pathauto, paragraphs |
| Hosting | Pantheon (settings.pantheon.php) con CDN | HTTP/2 y HTTP/3 (`alt-svc: h3`), Brotli activo en HTML |
| Certificado | HTTPS ok | Certificado válido; sin problemas observados |
| Lenguaje | `es` | `<html lang="es">`, vista móvil correcta |

**Hallazgo importante:** el caché de página anónimo no está sirviendo respuestas caché (`X-Drupal-Cache: MISS` y `X-Drupal-Dynamic-Cache: MISS` en cada petición; `Cache-Control: private, no-cache`). Esto explica un TTFB alto: cada visita anónima vuelve a renderizar PHP.

### 1.1.2 Velocidad y Core Web Vitals (medidos en navegador controlado, 25/09/2026)

| Métrica | Valor medido | Referencia recomendada | Estado |
|---|---|---|---|
| TTFB (inicio de respuesta) | ~3,7 s (curl ~2,2–2,5 s) | < 0,8 s | 🔴 Deficiente |
| FCP | ~4,4 s | < 1,8 s | 🔴 Deficiente |
| LCP | ~5,3 s | < 2,5 s | 🔴 Deficiente |
| Tiempo de carga total | ~5,2 s | — | 🔴 Alto |
| Peso total página | ~4,1 MB de transferencia | objetivo < 1,5 MB | 🔴 Alto |
| Recursos | 28 CSS + 60 JS + 7 imágenes | — | 🔴 Excesivo |
| Caché de estáticos | `public, max-age=604800` (7 días) | — | 🟡 Mejorable (assets con hash pueden usar 1 año) |
| Imágenes | Hero de 1,45 MB (`foto_andres…png.avif`); sin `sizes`, carga diferida solo en 4 imágenes | < 200 KB por imagen | 🔴 Crítico |

Causas probables: caché de página anónimo apagado, ~90 recursos de CSS/JS (tema Tailwind + numerosos módulos) sin agregación efectiva, imágenes de gran tamaño sin presets ni lazy-load, y módulos pesados cargados globalmente (p. ej. `drupal/sitemap` y `drupal/xmlsitemap` están ambos instalados — duplicidad de sitemaps).

**Core Web Vitals definitivos** (puntuación CrUX/PageSpeed de campo) **NO verificados**: requieren acceso a Search Console/PageSpeed Insights con clave. Los valores lab antes medidos son indicativos del problema.

### 1.1.3 Indexabilidad

| Aspecto | Estado |
|---|---|
| robots.txt | Correcto. Referencia `Sitemap: https://saibher.com/sitemap.xml` y desbloquea estándar de Drupal. |
| Sitemap | **Problemas:** 359 URLs, de las cuales ~280 son etiquetas de taxonomía (contenido fino, `index,follow`), 34 son URLs de administración `/media/N/edit` (responden 403 y no deberían indexarse ni estar en el sitemap), y 1 es `/node/29` sin alias amigable. Contenido real indexable: ~41 artículos + ~12 páginas principales. |
| Meta robots | `index, follow` global. Las etiquetas de taxonomía no tienen `noindex`. |
| Canonical | **BUG en la home:** `<link rel="canonical" href="/home-2">` y `/home-2` responde 301 → `https://saibher.com/`. El canonical debe ser la URL final. Páginas de servicio usan canonical absoluto correcto. |
| Redirecciones de dominio | `http://saibher.com` responde 200 sin redirigir a HTTPS; `www.saibher.com` también responde 200 (contenido varía ligeramente). No hay una redirección 301 de dominio canónico → riesgo de contenido duplicado y de servir sobre HTTP. El `.htaccess` raíz no fuerza HTTPS ni www. |
| 404 | Manejo correcto (código 404; módulo `search404` instalado). |
| Favicon | **404** (`/favicon.ico` no existe). |
| Páginas duplicadas | `/home` responde 200 y duplica la home; `/drupal`, `/seo`, etc. (280 etiquetas) son páginas finas indexadas. |

### 1.1.4 Metadatos y jerarquía semántica

| Página | Title | Meta description | H1 |
|---|---|---|---|
| `/` (home) | "Tecnología cercana para comercios y negocios reales \| saibher" ✅ | Correcta ✅ | 1 H1 ✅ |
| `/blog` | "Blog \| saibher" 🟡 (genérico) | Correcta ✅ | ❌ sin H1 |
| `/andres-rincon` (portafolio) | "Andrés Rincón desarrollador Drupal \| saibher" ✅ | Correcta ✅ | 1 H1 ✅ |
| `/desarrollo-web-saibher` | "Desarrollo Web A Medida \| saibher" 🟡 | Correcta pero corta | ❌ sin H1 (encabeza con H2) |
| `/servicios-drupal` | "Desarrollo y soporte en Drupal \| saibher" 🟡 | Correcta pero corta | ❌ sin H1 |
| `/sistema-de-punto-de-ventas-0` | "sistema de punto de ventas \| saibher" 🔴 (minúsculas, alias pobre `-0`) | **Ausente** ❌ | ❌ sin H1 |
| `/portafolio` | "Portafolio Andrés Felipe Rincón \| saibher" ✅ | Correcta | - |

- **Jerarquía rota en páginas de servicio:** varias comienzan con `<h2>` como encabezado principal (sin H1). La home usa H1→H3 de forma correcta.
- Meta descriptions débiles o ausentes en las páginas clave de conversión.
- Aliases de contenido finos con nombres tipo keyword ("-drupal", "-0", "-1") que restan calidad percibida.

### 1.1.5 Datos estructurados (schema.org / JSON-LD)

- ❌ **No hay ningún JSON-LD en el sitio** (home, blog, artículos ni servicios). Se instaló el módulo `schema_metatag`, pero **no está configurado** para emitir datos.
- Ausentes: `Organization`, `WebSite`, `LocalBusiness`/`ProfessionalService`, `Article` (con autor y fecha), `Person`, `FAQPage` (la home tiene bloques FAQ reales sin marcado), `Service`/`Product` para el POS.
- Consecuencia directa para GEO: los motores de IA no pueden estructurar la entidad "Saibher", ni su negocio, ni sus precios, ni sus FAQs.

### 1.1.6 Otros

- ❌ Sin Open Graph ni Twitter Cards (los bloques FAQ del home usan `addtoany` para compartir, pero sin OG no hay tarjetas bonitas).
- ❌ Sin `hreflang` (módulo instalado, sin configurar; el sitio es solo `es`).
- GA4 configurado vía `google_tag` (dos IDs `G-8C5MY41CR4` y `G-SS308LYJ1J` medidos en la home — posible doble conteo).
- El tema es custom (Tailwind), responsive, con `viewport` correcto.

---

## 1.2 SEO de contenido

### 1.2.1 Estructura de contenidos actual

- **Home:** bien escrita, orientada al negocio, con bloques FAQ de calidad (POS, desarrollo a medida, seguridad Drupal, licencia vitalicia). Es el activo GEO más aprovechable del sitio.
- **Blog:** 41 artículos. Distribución por fecha: **37 en 2023, 4 en 2024** (la última publicación visible: junio 2024). Inactivo >1 año.
- **Orientación:** abrumadoramente *técnica* para desarrolladores (Drupal, Batch, QueueWorker, React, PHP, Tailwind). Solo algunos tocan el negocio (POS, inventario, ecommerce). Los clientes objetivo (pequeños comercios en Colombia) no buscan esos temas.
- **Servicios:** 3–4 páginas finas y genéricas (desarrollo web, Drupal, POS) sin profundidad, sin precios, sin casos de éxito, sin preguntas frecuentes de servicio y sin enfoque local (Bogotá/Villavicencio/Colombia).
- **Portafolio:** página personal de Andrés con tecnologías; **no hay casos de éxito con resultados**. El proyecto `/proyecto/saibher` es el único "ítem" y describe la propia web.
- **Newsletter:** hay formulario de suscripción (módulo simplenews/campaignmonitor instalado).

### 1.2.2 Palabras clave: coberturas y oportunidades

Cobertura actual tolerable: tutoriales Drupal/PHP (intención de desarrollador), "Drupal", "desarrollo web".

**Oportunidades no cubiertas (intención de cliente, alta demanda en Colombia):**

| Clave / tema | Por qué importa | Estado Saibher |
|---|---|---|
| "sistema de punto de venta Colombia" / "POS Colombia" | Dominado por Alegra/Siesa; nicho SMB real | 🔴 Sin página optimizada |
| "punto de venta a la medida" / "POS personalizado" | Poca oferta custom seria en SERP | 🔴 Sin cobertura |
| "POS licencia única / sin mensualidad Colombia" | Diferenciador único de Saibher; casi nadie lo ofrece (solo softwarepos.co "pago único") | 🔴 Sin página dedicada |
| "ERP / software de gestión a medida Colombia" | SERP dominada por SaaS; poco custom | 🔴 Sin cobertura |
| "cuánto cuesta una página web en Colombia" / "cuánto cuesta una web a medida" | Alta intención, respuestas débiles | 🔴 Sin cobertura |
| "Drupal vs WordPress para pymes" | Comparativa local casi inexistente | 🔴 Sin cobertura |
| "migrar Drupal 10 a 11" / "actualizar Drupal Colombia" | Solo a nivel enterprise (SeedEM, Coresis) | 🟡 Un post (9.5→10.2) técnico, no comercial |
| "web/tienda para restaurante/tienda de barrio Colombia" | Mercado SMB enorme | 🔴 Sin cobertura |
| "sistema POS DIAN / facturación" | Tema normativo de alta demanda | 🔴 Sin cobertura |

**Hallazgo de investigación (VERIFICADO por snippet de búsqueda):** una ruta indexada del sitio aparece en Google con el fragmento "Sitio Suspendido". Conviene auditar qué URL es y si hay páginas huérfanas o errores de servidor que indexar.

### 1.2.3 Frecuencia y consistencia

- Frecuencia histórica: ~3-4 artículos/mes durante marzo-mayo 2023, luego se desaceleró **Hasta c. junio 2024**. Sin calendario editorial actual.
- Veredicto: **frecuencia inexistente en el último año**, lo que degrada la señal de relevancia y la oportunidad de autoridad temática.

---

## 1.3 Autoridad y presencia externa

### 1.3.1 Backlinks (VERIFICADO/ESTIMADO)

- **Conteo:** NO VERIFICADO con datos de índice (Sin acceso a Ahrefs/Semrush/Moz). **ESTIMADO: muy pocos dominios referentes (< 50), casi todos perfiles propios.**
- Perfiles propios con enlace (⚠️ equivalentes a auto-backlinks de baja señal):
  - **Drupal.org:** `drupal.org/u/saibher` — dominio de máxima autoridad; perf il 3 años. ✅ VERIFICADO
  - **GitHub:** `github.com/technomundohmr` (19 repos, 0 seguidores), website → saibher.com. ✅ VERIFICADO
  - **git.drupalcode.org/saibher** ✅ VERIFICADO
  - **LinkedIn personal:** `linkedin.com/in/andrés-felipe-rincón-gracia-513982215` (~57 seguidores). ✅ VERIFICADO
- **Activo real en Drupal.org:** issue de core reportado por "saibher" (oct 2025) — señal de credibilidad técnica aprovechable. ✅ VERIFICADO
- **Sin menciones externas:** ningún blog, listado o artículo cita a saibher.com. ✅ VERIFICADO (no aparece en listados)
- **Sin directorios:** no aparece en Clutch, GoodFirms, DesignRush, directorio de agencias Drupal.org, Cámara de Comercio, etc. ✅ VERIFICADO (busq. sin resultados)

### 1.3.2 Redes y marca

- YouTube: canal "Ahora Saibher" existe (suscriptores NO VERIFICADOS). ✅ existe
- LinkedIn Company de Saibher: **NO existe** (solo perfil personal). ❌
- Instagram / X / Facebook: **no encontrados**. ❌ (Ojo: hay ruido con "Saiber", "SAIBER LLC", "saibersaiber.com" — entidades distintas)
- **Confusión de marca:** el desarrollador usa "saibher" y "technomundohmr" de forma indistinta (GitHub, Teachlr, repos). La equidad de enlaces se dispersa entre ambas identidades.

### 1.3.3 Local / reseñas

- **Google Business Profile: NO existe / NO reclamado** (no hay ficha en Maps, ni reseñas, ni NAP). ❌ Es la carencia local más grave: Saibher es un negocio de servicios en Colombia operando sin identidad local verificable.

---

## 1.4 GEO — posicionamiento en IA generativa

### 1.4.1 Hipótesis de citabilidad actual

Para que un asistente (ChatGPT, Perplexity, Copilot, AI Overviews) cite una fuente esta debe ser: clara en estructura, con datos estructurados, respuestas directas, autoría y entidad reconocible, actualizada y con autoridad. Saibher hoy:

- **Formato de respuestas directas:** ✅ bloques FAQ de la home responden preguntas concretas ("¿Qué es un sistema de punto de ventas?", "¿Cada cuánto debo pagar la licencia?"). Muy citables si se marcan con `FAQPage`.
- **Datos estructurados:** ❌ nulos. Las IA no pueden leer entidad/negocio/persona de forma automatizada.
- **Autoría/entidad:** ❌ los artículos no muestran autoría consistente con perfil; la entidad "Andrés Rincón / Saibher" está dispersa (LinkedIn, Drupal.org, GitHub bajo dos nombres).
- **Actualización:** ❌ contenidos >1 año sin actualizar → las IA priorizan lo reciente.
- **Autoridad:** ❌ sin backlinks ni citas externas ni listados → bajo "probabilidad de ser referenciado".

### 1.4.2 Presencia observada en respuestas de IA/rankings

- Búsquedas tipo "punto de venta para negocio en Colombia" y "desarrollo Drupal Colombia": las fuentes que citan las IA son Alegra, Siesa, SeedEM, Coresis, y blogs comparativos. **saibher.com: NO APARECE.** (VERIFICADO por ausencia en todas las SERPs y listados revisados.)
- El único activo citable para IA es el **perfil de Drupal.org**, no la web.
- Nicho defendible: **Drupal + POS/ERP a medida con licencia vitalicia** es exactamente el hueco que ni los grandes ERPs ni las agencias genéricas cubren; es ganable.

---

## 1.5 Competencia (Colombia)

| Competidor | Foco | Blog/contenido | Señales SEO/técnicas | Autoridad |
|---|---|---|---|---|
| **seedem.co** | Drupal enterprise (GOV.CO, Drupal Partner) | Hub de contenido profundo, EN+ES, servicio AEO explícito | URLs largas por servicio, páginas de sector, AEO | Certificaciones Drupal, clientes de gobierno |
| **coresis.info** | Ingeniería Drupal (gobierno) | Silo de aterrizajes long-tail ("expertos-drupal-colombia", "migración a Drupal 11") | FAQ on-page, HTML semántico | Min. Vivienda, ICBF, Metro de Bogotá |
| **alegra.com** | SaaS contabilidad/POS/ERP SMB | Cadencia altísima, centro de ayuda, normativa DIAN | FAQ/pricing estructurado, volumen enorme | Autoridad de dominio enorme, autorizado DIAN |
| **siesa.com** | ERP incumbente (45 años, 10k+ clientes) | Posts fechados recientes | Megacontenedores verticales, páginas de cumplimiento | "Único PST autorizado DIAN", peso de dominio |
| **saibher.com** | **Drupal + POS/ERP a medida, licencia vitalicia** | **Blog inactivo desde 2024** | **Sin schema; errores técnicos** | **Débil: solo perfiles propios** |

**Complementarios SMB útiles como referencia:** `aesspos.com`, `poscolombia.com`, `softwarepos.co` (este último también ofrece pago único, modelo idéntico al de Saibher).

**Conclusión competitiva:** en "desarrollo Drupal Colombia" Saibher no compite (sin páginas, sin autoridad); en "POS/ERP SMB" compite contra SaaS con enorme autoridad. **La única jugada ganadora es el hueco custom + licencia vitalicia**, que nadie cita y donde Saibher tiene diferenciador real.

---

# Fase 2 — Plan de acción

## 2.1 Quick wins a corto plazo (0-4 semanas, alto impacto / bajo esfuerzo)

**Técnicos (prioridad máxima):**

1. **Corregir canonical de la home** → `https://saibher.com/` (configurar Metatag del nodo de portada; eliminar dependencia del alias `/home-2`). Corregir también `/home` (301 al dominio o fuera de sitemap).
2. **Unificar dominio y protocolo:** 301 de `http://` → `https://` y de `www.saibher.com` → `https://saibher.com/` (`.htaccess` / reglas Pantheon). Evita duplicados y sirve siempre seguro.
3. **Habilitar caché de página anónimo** (`cache.page.max_age`) y **agregar CSS/JS en producción**. Reducirá TTFB de ~3,7 s a <1 s.
4. **Optimizar la imagen "hero"** (1,45 MB): redimensionarla (≤ 1280px), comprimir a <200 KB, añadir `width/height + sizes` y `loading="lazy"` a las imágenes fuera del viewport.
5. **Limpiar el sitemap:** eliminar las 34 URLs `/media/*/edit` (403) y el `/node/29`; aplicar `noindex` a las ~280 etiquetas de taxonomía delgadas (o consolidarlas en pocos hubs temáticos). Dejar ~60 URLs de calidad.
6. **Activar `schema_metatag`:** emitir en la home `Organization` (con LinkedIn/YouTube/Drupal.org) + `WebSite` + `FAQPage` para los bloques FAQ; en artículos `Article` con `author` (Person) y `datePublished`; en servicios `Service`/`Product` (incluido precio/rango para el POS).
7. **Añadir Open Graph/Twitter Cards** en todo el sitio y crear un **favicon** (hoy 404).
8. **Añadir H1 a las páginas de servicio** (desarrollo web, Drupal, POS) y **reescribir title/meta description**, incl. la página POS (title en minúsculas y sin description).
9. **Corregir doble GA4** (dos IDs simultáneos) y consolidar módulos de sitemap (instalados `sitemap` y `xmlsitemap`).
10. **Configurar robots.txt con user-agents de IA** (permitir explícitamente GPTBot, ClaudeBot, PerplexityBot, Google-Extended-bot) manteniendo la actual.

**Contenido (quick wins):**

11. Publicar 2 contenidos "top of funnel" inmediatos: *"Cuánto cuesta una página web a medida en Colombia (guía de precios 2026)"* y *"Sistema punto de venta para pequeño negocio en Colombia: SaaS vs licencia vitalicia"* (con FAQPage).
12. Actualizar los 3 artículos con más tráfico/posición con fecha y datos de 2026 (señal de frescura para IA).

## 2.2 Plan de contenido a mediano plazo (meses 1-6)

**Estrategia:** cambiar el eje del blog de *desarrollador* a *comprador de tecnología para negocios*, manteniendo 1 tutorial técnico/mes como ancla de autoridad y enlaces.

**Calendario sugerido — 2 publicaciones/mes (24 en 6 meses):**

| Pilar | Temas (título tipo pregunta) | Frecuencia |
|---|---|---|
| **A. POS para negocios** | "Qué es un sistema POS y cómo elegirlo en Colombia"; "POS SaaS vs licencia vitalicia: cuál conviene"; "POS para tienda/supermercado"; "Facturación electrónica DIAN y tu POS"; "POS + inventario + tienda online sincronizados" | 1/mes |
| **B. Software/ERP a medida** | "Software de gestión a medida: cuándo conviene vs SaaS"; "Cuánto cuesta un ERP para pyme en Colombia"; "Control de inventario simple con software a medida"; "Cómo migrar de Excel a software de gestión" | 1/mes |
| **C. Drupal para negocios** | "Drupal vs WordPress para pyme en Colombia"; "Cuánto cuesta un sitio Drupal"; "Migración a Drupal 11: pasos y costos"; "Drupal para tiendas/portales" | 1 c/2 meses |
| **D. Desarrollo web & conversión** | "Cuánto cuesta una página web en Colombia (guía de precios)"; "Web para restaurante"; "SEO para pymes 2026"; "PageSpeed: por qué tu web es lenta" | 1 c/2 meses |
| **E. Autoridad técnica (developers)** | Tutoriales Drupal/PHP actuales (Drupal 11, módulos) para reforzar Drupal.org/GEO técnico | 1/mes |

**Formato obligatorio por artículo:** respuesta directa en los primeros 100-150 caracteres, H2 en formato pregunta, bloque FAQ final con `FAQPage`, autoría visible (Andrés Rincón + bio+LinkedIn), datos verificables (precios en rangos, fechas, fuentes DIAN/Drupal.org).

**Además (meses 1-6):**
- **3-4 casos de éxito** por trimestre: página de cada proyecto con reto/solución/resultado medible. Portafolio actualmente no tiene casos de éxito.
- **Páginas de servicio renovadas** con FAQ + precios orientativos + testimonios.
- **Página de localización:** "Desarrollo web y software en Bogotá" y "… en Villavicencio/Meta" (si existe presencia local) y "… en Colombia".

## 2.3 Estrategia de autoridad / backlinks (meses 1-9)

1. **Google Business Profile (ABSOLUTA PRIORIDAD):** crear/reclamar ficha Bogotá (y sucursal Villavicencio si aplica), categoría, NAP consistente, fotos, reseñas de clientes reales, y enlazar desde el footer. Resuelve a la vez el local SEO y la entidad para IA.
2. **Drupal.org:** completar perfil (foto, bio, enlaces), listar módulos/temas contribuidos, solicitar alta en el **directorio de agencias/proveedores soporte Drupal.org**, y publicar el issue/contribución del core como señal. Estás a ~3 años en la plataforma; es el activo de autoridad #1.
3. **Comunidad:** integrarse a la **Asociación Drupal Colombia** (`drupal.org/project/drupalcolombia`) y al canal @drupalco; hablar en Drupal Camp Colombia / DrupalCon LATAM (bio + mención del sitio). Los eventos publican en YouTube (backlink bueno + citas).
4. **LinkedIn Company:** crear página de empresa de Saibher (hoy solo existe perfil personal), importar conexiones, publicar los casos de éxito y conectar a Andrés. Backlink de autoridad + canal B2B.
5. **Directorios de agencias:** Clutch, GoodFirms, DesignRush, Sortlist (industria TI/software Colombia) + informa Colombia y Cámara de Comercio (nap consistente).
6. **Publicaciones invitadas / outreach:** contactar The Drop Times, Drupal Weekly y blogs dev de LATAM con los tutoriales técnicos (p. ej. migración 9.5→10.2/11). 2-3 búsquedas de enlaces por trimestre.
7. **GitHub:** repos `saibher`/`saibher_bms` con README que enlacen al sitio; contribuir a proyectos de Drupal desde la cuenta para que GitHub pase referencias.
8. **Unificar marca:** decidir entre "saibher" y "technomundohmr" para los perfiles públicos (recomendado: resolverlos hacia Saibher) — la equidad actual se divide entre ambas.
9. **PR digital:** 1-2 notas/año con dato original ("X% de pequeños comercios colombianos usan Excel" encuesta propia) para citas de prensa especializada → enlaces con contexto.

## 2.4 Estrategia específica de GEO (qué hacer para que la IA te cite)

La optimización GEO difiere del SEO clásico: **el objetivo es ser la respuesta verificable y estructurada**, no la más densa en keywords.

1. **Entidad clara y consistente:** una sola identidad de marca "Saibher" con (a) `Organization` + `Person` schema en home y en todos los artículos con `sameAs` (LinkedIn, YouTube, Drupal.org, GitHub); (b) NAP idéntico en Google Business, web y directorios; (c) autoría visible en cada artículo (nombre, cargo, bio + link).
2. **Respuestas directas "de diccionario":** cada página/artículo debe contestar su pregunta objetivo en el primer párrafo (patrón "¿Qué es X?" → definición en <100 palabras). Así las IA extraen el fragmento sin ambigüedad.
3. **FAQPage schema en todos los contenidos con preguntas** (ya hay bloques FAQ reales en la home: solo falta el marcado). Las preguntas deben coincidir con "People Also Ask" y con cómo pregunta la gente a ChatGPT.
4. **Datos originales y verificables:** rangos de precios (Colombia), referencia a la normativa DIAN, cifras de casos de éxito con años. Las IA citan más lo que tiene números, fechas y fuentes.
5. **Autoridad temática:** los pilares A-E (2.2) crean clusters interconectados; las IA priorizan sitios con profundidad demostrada y hubs actualizados.
6. **Frescura:** actualizar fechas y datos continuamente (las IA penalizan contenido obsoleto: varios posts tienen >1 año).
7. **Crawlabilidad IA:** permitir explícitamente `GPTBot`, `ClaudeBot`, `PerplexityBot`, `Google-Extended` en robots.txt + sitemap limpio y estable.
8. **Fast + clean:** páginas rápidas (fixes de 2.1) y HTML semántico (H1/H2 correctos) — señal de calidad para los extractores de IA.
9. **Probar la citabilidad (loop de verificación):** preguntar a ChatGPT/Perplexity/Copilot: "¿mejores agencias Drupal en Colombia?", "¿qué es un POS con licencia vitalicia?", "¿cuánto cuesta una web a medida en Colombia?" y monitorear si Saibher aparece como fuente. Iterar sobre las respuestas.

**Meta GEO (6-12 meses):** aparecer como fuente en ≥2 de 5 consultas objetivo de IA; tener ≥10 citas del dominio en AI Overviews/Perplexity para el cluster POS + Drupal Colombia.

## 2.5 Métricas de seguimiento

| Área | Métricas | Fuente / herramienta | Cadencia |
|---|---|---|---|
| **Ranking** | Posiciones de 20-25 keywords objetivo (los de 1.2.2) | Google Search Console + rank tracker (ej. Wincher/Semrush free) | Mensual |
| **Tráfico** | Clics/impresiones orgánicas, CTR, % de tráfico por pilar | GSC + GA4 | Semanal/ mensual |
| **Conversiones** | Llamadas formularios de contacto, suscripciones newsletter (objetivo: clics en "Necesito asesoría") | GA4 (eventos) + que una llamada a la acción llegue a servicio | Mensual |
| **CWV** | LCP <2,5s, CLS <0,1, INP <200ms (core), TTFB <0,8s | PageSpeed Insights API / CrUX | Mensual |
| **Indexabilidad** | Páginas indexadas vs sitemap (~60 objetivo) | GSC "Indexación" | Mensual |
| **Backlinks** | nº de dominios referentes (objetivo +10 trimestral) | Semrush/Ahrefs free + GSC enlaces | Trimestral |
| **Local** | Impacto GBP (llamadas, "cómo llegar", reseñas) | Google Business Profile | Mensual |
| **Menciones IA (GEO)** | ¿Aparece saibher.com como fuente en 5 consultas objetivo en ChatGPT/Perplexity/Copilot/AI Overviews? | Sondas manuales automatizables; herramientas de terceros (p. ej. Panabee/AI SUO) cuando el presupuesto lo permita | Mensual |

**KPIs prioritarios a 12 meses:** tráfico orgánico x4, ≥10 dominios referentes nuevos, 3 casos de éxito publicados, posiciones top-10 en 5 keywords del cluster "POS/ERP a medida" y 3 del cluster "Drupal Colombia", y ≥2 citas en respuestas de IA.

---

# Decálogo de acciones más importantes (resumen final)

1. Arreglar los bugs técnicos de indexación (canonical home, dominio https/www, sitemap limpio).
2. Acelerar el sitio (caché anónimo + hero 1,45 MB → <200 KB) para pasar CWV.
3. Activar schema.org / JSON-LD en todo el sitio (entidad, artículos, FAQ, servicios).
4. Reactivar el blog orientado al comprador con 2 posts/mes y casos de éxito.
5. Crear/reclamar Google Business Profile + LinkedIn Company + directorios (Clutch, Drupal.org).

---

# Tareas — desglose de ejecución técnica

> **Propósito de esta sección.** Traduce el plan de acción de la Fase 2 en tareas individuales, ejecutables y verificables para el proyecto Drupal 11 (tema custom `saibher`). El diagnóstico completo permanece en la **Fase 1**; aquí solo se **referencia** (§1.x) y se define el *cómo*.
>
> **Cómo se lee.** Cada tarea tiene: `Objetivo`, `Entregables` (verificables), `Criterios de aceptación` (comprobables) y `Dependencias`. Se recomienda crear en el tracker un issue por tarea y marcar los criterios como checklist de cierre.
>
> **Módulos base ya instalados en `composer.json` (no hay que añadir salvo indicación):** `metatag`, `schema_metatag`, `pathauto`, `redirect`, `xmlsitemap`, `robotstxt`, `google_tag` (GA4), `search404`, `yoast_seo`, `easy_breadcrumb`, `scheduler`, `hreflang`, `seo_checklist`. Duplicado a resolver: `sitemap` (drupal/sitemap) → **T-03**.

## Vista general

| ID | Tarea | Fase | Prioridad |
|---|---|---|---|
| T-01 | Corregir canonical de la home | A · Indexación | P0 |
| T-02 | Unificar HTTPS + www (301) | A · Indexación | P0 |
| T-03 | Consolidar sitemap XML y limpiarlo | A · Indexación | P0 |
| T-04 | `noindex` de etiquetas/taxonomía finas | A · Indexación | P0 |
| T-05 | robots.txt con user-agents de IA | A · Indexación | P1 |
| T-06 | URLs amigables + redirecciones (pathauto/redirect) | A · Indexación | P1 |
| T-07 | H1 en páginas de servicio (tema) | A · Semántica | P1 |
| T-08 | Titles/metas globales + por tipo de contenido | A · Metadatos | P0 |
| T-09 | Open Graph, Twitter Cards y favicon | A · Metadatos | P1 |
| T-10 | Activar caché de página anónima | A · Velocidad | P0 |
| T-11 | Agregación CSS/JS y assets condicionales | A · Velocidad | P1 |
| T-12 | Optimizar hero, lazy-load y dimensión de imágenes | A · Velocidad | P0 |
| T-13 | Schema `Organization` + `WebSite` | A · Datos estructurados | P0 |
| T-14 | Schema `Article` + `Person` (autoría) | A · Datos estructurados | P0 |
| T-15 | Schema `FAQPage` (FAQ home y nuevas) | A · Datos estructurados | P0 |
| T-16 | Schema `Service`/`LocalBusiness` (servicios) | A · Datos estructurados | P1 |
| T-17 | Estructura editorial del blog (campos, scheduler) | B · Contenido | P1 |
| T-18 | Publicar "Cuánto cuesta una web a medida en Colombia" | B · Contenido | P1 |
| T-19 | Publicar "POS: SaaS vs licencia vitalicia" | B · Contenido | P1 |
| T-20 | Calendario editorial 2 posts/mes (5 pilares) | B · Contenido | P1 |
| T-21 | Página de servicio POS dedicada | B · Contenido | P1 |
| T-22 | Casos de éxito (3-4 proyectos) | B · Contenido | P2 |
| T-23 | Páginas de localización (Bogotá, Villavicencio/Meta, Colombia) | B · Contenido | P2 |
| T-24 | Frescar artículos de 2023-2024 | B · Contenido | P2 |
| T-25 | Crear/reclamar Google Business Profile | C · Autoridad | P1 |
| T-26 | Crear LinkedIn Company Saibher | C · Autoridad | P1 |
| T-27 | Completar Drupal.org + directorio de agencias | C · Autoridad | P2 |
| T-28 | Comunidad Drupal Colombia + DrupalCon | C · Autoridad | P2 |
| T-29 | Directorios de agencias (Clutch, GoodFirms, DesignRush…) | C · Autoridad | P2 |
| T-30 | Outreach y publicaciones invitadas | C · Autoridad | P2 |
| T-31 | Unificar marca (saibher/technomundohmr) + GitHub | C · Autoridad | P2 |
| T-32 | PR digital con datos originales | C · Autoridad | P3 |
| T-33 | Plantilla de "respuesta directa" (guía editorial) | D · GEO | P1 |
| T-34 | Autoría visible en todos los artículos | D · GEO | P1 |
| T-35 | Preguntas PAA + FAQ en cada artículo | D · GEO | P1 |
| T-36 | Entidad y NAP consistente | D · GEO | P1 |
| T-37 | Sonda de citabilidad mensual en IA | D · GEO | P1 |
| T-38 | Auditar ruta "Sitio Suspendido" y huérfanas | D · GEO | P2 |
| T-39 | GA4 consolidado + eventos de conversión | E · Métricas | P1 |
| T-40 | Verificar GSC + Bing y enviar sitemap | E · Métricas | P0 |
| T-41 | Línea base y panel de keywords | E · Métricas | P1 |
| T-42 | Monitor de Core Web Vitals mensual | E · Métricas | P1 |
| T-43 | Proceso de reporte mensual | E · Métricas | P2 |

---

## Fase A — Quick wins técnicos

### T-01 · Corregir el canonical de la home

**Objetivo:** Eliminar el bug de canonicidad detectado en §1.1.3 (la home emite `<link rel="canonical" href="/home-2">`, y `/home-2` responde 301 a `/`), de forma que Google e indexadores compartidos vean una única URL canónica.

**Entregables:**
- Configuración de Metatag para el nodo de portada con canonical = `https://saibher.com/`.
- El alias `/home-2` eliminado de pathauto (o 301 permanente a `/`).
- Verificación documentada: `curl -s https://saibher.com | grep -o 'rel="canonical"[^>]*'`.

**Criterios de aceptación:**
- `https://saibher.com` emite `rel="canonical"` → `https://saibher.com/` (no relativo, no `/home-2`).
- `https://saibher.com/home-2` responde 301 a `https://saibher.com/`.
- Ninguna otra página principal emite canonical relativo roto (§1.1.3: blog y `andres-rincon` rutas atómicas — revisados).

**Módulo(s):** `metatag`, `pathauto`, `redirect`
**Dependencias:** acceso de administrador al sitio y a Search Console (para re-crawleo posterior).

### T-02 · Unificar protocolo HTTPS y dominio www (301)

**Objetivo:** Forzar HTTPS y elegir `https://saibher.com/` (sin www) como canonical global, eliminando el contenido duplicado y el servicio sobre HTTP observados en §1.1.3.

**Entregables:**
- Reglas de reescritura en el `.htaccess` raíz (que actualmente solo hace `RewriteRule` hacia `/web/`) o, si el hosting/es Pantheon, reglas emitidas por el edge/CDN.
- Comprobación de: `http://saibher.com` → 301 → `https://saibher.com/` y `https://www.saibher.com` → 301 → `https://saibher.com/`.

**Criterios de aceptación:**
- `curl -s -o /dev/null -w "%{http_code} %{redirect_url}" http://saibher.com` devuelve `301 https://saibher.com/`.
- `curl … https://www.saibher.com` devuelve `301 https://saibher.com/`.
- El sitio no responde 200 nunca sobre `http://` (aunque sea para servir la redirección).

**Módulo(s):** ninguno (servidor/edge). Appl. hosting propio `.htaccess`.
**Dependencias:** acceso al hosting/panel (o acción sobre Pantheon edge). Recomendado tras T-01 (canonical) para no mezclar dos señales a la vez.

### T-03 · Consolidar el sitemap XML y limpiarlo

**Objetivo:** Resolver el diagnóstico de §1.1.3: web contiene 359 URLs, de las cuales ~34 son `/media/*/edit` (403), 280 etiquetas finas y `/node/29`; dejar un sitemap con solo contenido útil (~65 URLs).

**Entregables:**
- Decisión documentada del módulo de sitemap en uso: mantener `xmlsitemap` y **desinstalar `drupal/sitemap`** (duplicidad, §1.1.1).
- Configuración de `xmlsitemap`: solo content types útiles (`articulo`, `pagina_basica`, `proyecto`/caso de éxito), excluir taxonomía y `media` de todas las rutas.
- `https://saibher.com/sitemap.xml` regenerada y sin rutas de administración ni etiquetas.

**Criterios de aceptación:**
- `curl -s https://saibher.com/sitemap.xml | grep -c "media/"` = 0.
- `curl -s … | grep -c "node/"` = 0.
- El número de `<url>` en el sitemap está entre 40 y 100.
- En producción un solo módulo de sitemap activo (`drush pml | grep -i sitemap`).

**Módulo(s):** `xmlsitemap` (mantener), `drupal/sitemap` (desinstalar), `metatag`.
**Dependencias:** T-04 (noindex de etiquetas) para que excluirlas del sitemap sea coherente.

### T-04 · Aplicar `noindex` a etiquetas y taxonomía finas

**Objetivo:** Evitar que las ~280 URLs de taxonomía (p. ej. `/drupal`, `/seo`) —contenido fino delgado (§1.1.3, §1.2)— se indexen y deterioren la calidad percibida del dominio.

**Entregables:**
- Configuración de Metatag para el tipo de entidad *taxonomy term*: `robots: noindex, follow`.
- Si se decide conservarlas para usuarios, mantenerlas navegables (sin bloquear en robots.txt para no perder enlaces internos).

**Criterios de aceptación:**
- `curl -s https://saibher.com/drupal | grep -o 'name="robots"[^>]*'` → `noindex, follow`.
- Tras recrawleo (GSC), "Cobertura" sin URLs de taxonomía marcadas como indexadas.
- Enlaces internos a páginas útiles desde las etiquetas siguen operativos.

**Módulo(s):** `metatag`.
**Dependencias:** ninguna. Vinculada a T-03 (sitemap).

### T-05 · robots.txt con user-agents de IA permitidos

**Objetivo:** Asegurar crawl acceso de asistentes generativos (GPTBot, ClaudeBot, PerplexityBot, Google-Extended) a saibher.com, requisito GEO (§2.4.7); y confirmar que las reglas actuales no bloquean contenido útil.

**Entregables:**
- Configuración del módulo `robotstxt` (custom robots.txt): bloques `User-Agent: GPTBot`, `User-Agent: ClaudeBot` (y `CCBot`), `User-Agent: PerplexityBot`, `User-Agent: Google-Extended` con `Allow: /` (y sin `Disallow` no deseados).
- Comprobación de que `/sitemap.xml` sigue referenciado.

**Criterios de aceptación:**
- `curl -s https://saibher.com/robots.txt` incluye los 4 user-agents de IA con `Allow: /`.
- No existe `Disallow: /` ni bloqueos de `/blog`, `/assets` o `/sites/default/files` para usuarios estándar.
- Robots-test de GSC: 1 URL de muestra de cada tipo principal accesible.

**Módulo(s):** `robotstxt`.
**Dependencias:** T-03 (para que la referencia al sitemap sea la correcta).

### T-06 · URLs amigables y redirecciones (pathauto + redirect)

**Objetivo:** Corregir aliases de baja calidad detectados en §1.1.4 (p. ej. `/sistema-de-punto-de-ventas-0`, `/node/29`, `/home`) sin romper URLs existentes.

**Entregables:**
- Patrón `pathauto` coherente por content type (p. ej. artículos: `/blog/[node:field_tema]/[node:title]`; páginas de servicio: `/servicios/[node:title]`).
- Redirecciones 301 (módulo `redirect`) creadas para: `/sistema-de-punto-de-ventas-0` → `/servicios/sistema-de-punto-de-venta` (o el nuevo alias), `/node/29` → su nuevo alias, `/home` → `/`.
- Eliminar alias con sufijos `-0`, `-1`, `-2` innecesarios tras generar limpios.

**Criterios de aceptación:**
- Cada URL migrada responde 301 a su destino nuevo.
- `curl -s -o /dev/null -w "%{http_code}" https://saibher.com/node/29` → 301.
- En GSC "Páginas", ningún `node/*` ni `sistema-de-punto-de-ventas-0` en el inventario tras al menos 60 días.

**Módulo(s):** `pathauto`, `redirect`.
**Dependencias:** T-08 (metas) no bloqueante; hacerla antes o con la campaña de contenido para que las URL publicadas ya salgan limpias.

### T-07 · H1 en páginas de servicio (tema custom `saibher`)

**Objetivo:** Restaurar la jerarquía semántica rota (§1.1.4): las páginas `/desarrollo-web-saibher`, `/servicios-drupal` y `/sistema-de-punto-de-ventas-0` no emiten `<h1>` y encabezan con `<h2>`.

**Entregables:**
- Auditoría de templates del tema (`node--*.html.twig` en `web/themes/custom/saibher/src/templates/node` y componente `heroBanner`).
- Salida de un único `<h1>` por página de servicio (título del nodo o equivalente en el hero).
- Mantener exactamente un H1 por página (ninguna página con 0 o 2+).

**Criterios de aceptación:**
- `curl -s <url servico> | grep -c "<h1"` = 1 en las 3 páginas de servicio + home (ya tiene 1).
- `grep -c "<h2"` > 0 (no se pierden secciones).
- Lighthouse "Heading-levels" sin errores en las páginas auditadas.

**Módulo(s):** tema `saibher` (twig/preprocess), `Twig Tweak` (ya usado).
**Dependencias:** ninguna. Prioridad alta por su costo/impacto.

### T-08 · Titles y meta descriptions globales + por tipo de contenido

**Objetivo:** Cerrar los huecos de metadatos de §1.1.4: description vacía en la página POS, titles débiles en servicios, y establecer defaults tokenizados para todo el sitio.

**Entregables:**
- Campos de Metatag por content type: `basic page`, `article`, `project/casos`, con tokens `[node:title] | saibher` y descripciones únicas.
- Overrides por nodo para: home, `/desarrollo-web-saibher`, `/servicios-drupal`, página POS, blog, `/andres-rincon`, `/portafolio`, contactenos.
- Regla editorial de largo: title 50-60 caracteres, description 120-160.

**Criterios de aceptación:**
- Las 10 páginas principales tienen `title` y `meta name="description"` únicos y no vacíos.
- `meta description` de la página POS existe y reflaja el diferenciador (licencia vitalicia).
- Ningún `<title>` menor de 15 o mayor de 70 caracteres en páginas claves.

**Módulo(s):** `metatag`, `yoast_seo` (sugerencias opcional).
**Dependencias:** T-06 (para que las URL ya tengan nombre definitivo).

### T-09 · Open Graph, Twitter Cards y favicon

**Objetivo:** Cubrir el déficit de §1.1.6: actualmente sin `og:`/`twitter:card` y sin favicon (404). Mejora CTR en redes sociales, referencias compartidas y aspecto en resultados.

**Entregables:**
- Metatag global: `og:type`, `og:site_name`, `og:title`, `og:description`, `og:image` (imagen por defecto 1200×630) y `twitter:card summary_large_image`.
- Favicon (`/favicon.ico`) + `apple-touch-icon`.

**Criterios de aceptación:**
- `curl -s https://saibher.com` muestra `og:` y `twitter:` en HTML.
- `curl -s -o /dev/null -w "%{http_code}" https://saibher.com/favicon.ico` → 200.
- Compartir la home en Facebook/Twitter devuelve vista previa con imagen (verificable con el debugger).

**Módulo(s):** `metatag`, archivos del tema en `saibher.info.yml`/HTML.
**Dependencias:** T-08 (ya que comparte campos de Metatag).

### T-10 · Activar caché de página anónima

**Objetivo:** Reducir el TTFB de ~3,7 s a <1 s eliminando el renderizado PHP por petición de anónimos (§1.1.1: `X-Drupal-Cache: MISS`).

**Entregables:**
- En `/admin/config/development/performance`: página cache anónima activa con `max-age` adecuado (p. ej. 15 min–1 h) y compresión activa.
- Ajuste de `settings.php` para el entorno de producción (cache bin de página).
- Comprobación en doble petición: segunda con `X-Drupal-Cache: HIT`.

**Criterios de aceptación:**
- `curl -s -D- -o /dev/null https://saibher.com` (dos veces seguidas): la 2ª muestra `x-drupal-cache: HIT` y `Cache-Control: public, max-age=…`.
- TTFB medido en navegador < 1 segundo en carga con caché.
- No se cajan páginas con cookies de sesión ni el panel de administración.

**Módulo(s):** Core (Drupal internal page cache), variables de Pantheon (`cache.page.max_age`).
**Dependencias:** T-02 (HTTPS/www limpio) recomendado; bloques no bloquear el cache (`no-cache` por módulos como `antibot`/captcha si los hay).

### T-11 · Agregación CSS/JS y carga condicional de assets

**Objetivo:** Reducir los 28 archivos CSS + 60 JS y el transfer de 4,1 MB (§1.1.2) que inflan el render-blocking y el LCP.

**Entregables:**
- Activación de "Agregar y comprimir archivos CSS" y "…de archivos JavaScript" en `/admin/config/development/performance` (o equivalente en CI/Pantheon).
- Revisión de librerías de módulos: verificar que los que inyectan JS global (p. ej. `addtoany`, `google_tag`, `webform`, `canvas`, `mercury_editor`) carguen sus assets solo donde se usan (`states`/scoping en `*.libraries.yml` o preprocess).
- Descartar/desinstalar módulos no productivos (`examples`, `upgrade_status`, `diff` si no se usa en prod…).

**Criterios de aceptación:**
- En una carga anónima, nº de peticiones CSS/JS < 15 combinadas.
- `transferSizeTotal` (medible en navegador, §1.1.2) < 2 MB.
- El sitio funciona con agregación activa (sin errores de script / estilos).

**Módulo(s):** Core performance, `.libraries.yml` del tema, `drush`.
**Dependencias:** T-10 (caché) recomendado para verificar resultados sin recachés.

### T-12 · Optimizar imagen "hero", lazy-load y dimensiones

**Objetivo:** Atacar el LCP de ~5,3 s: la imagen hero pesa 1,45 MB y solo 4 imágenes cargan con `lazy` (§1.1.2).

**Entregables:**
- Estilo de imagen/responsive para el hero (máx. 1920 px) con formato AVIF/WebP servido vía `sizes`.
- Imágenes fuera del viewport con `loading="lazy"` (preprocess del tema en templates de artículo/teaser y home).
- Atributos `width`/`height` en todas las `<img>` para eliminar CLS; `sizes` en las responsive.

**Criterios de aceptación:**
- Hero descargado < 200 KB (verificable en Network del navegador o `curl -I` del asset).
- Las imágenes del home salvo la primera llevan `loading="lazy"`.
- CLS (lab) < 0,1; LCP (lab) < 2,5 s.

**Módulo(s):** Core image styles + responsive image, tema `saibher`.
**Dependencias:** T-11 (para que otros assets no dominen el LCP).

### T-13 · Schema `Organization` + `WebSite` (JSON-LD)

**Objetivo:** Atender §1.1.5 (sitio sin ningún JSON-LD): emitir en la home la entidad de negocio para Google y motores de IA.

**Entregables:**
- `schema_metatag` configurado en `metatag` → home: `Organization` (nombre, logo, `sameAs`: LinkedIn, YouTube, Drupal.org, GitHub) + `WebSite` con `SearchAction` no necesario (sin buscador interno) — al menos Organization.

**Criterios de aceptación:**
- `curl -s https://saibher.com | grep -c "application/ld+json"` ≥ 1.
- Validación en Google Rich Results: sin errores de `Organization` (nombre/logo resueltos).
- Los `sameAs` apuntan a perfiles existentes descubiertos en §1.3.

**Módulo(s):** `schema_metatag`, `metatag`.
**Dependencias:** T-02 (URL canónica única para no duplicar la entidad).

### T-14 · Schema `Article` + `Person` (autoría)

**Objetivo:** Dar a los posts del blog la estructura de autoría y fecha que requieren los motores de IA (§2.4), inexistente hoy (§1.1.5).

**Entregables:**
- Content type "articulo" con campos: autor (usuario/taxonomía autor) y fecha publicada (campo fecha o `node.created`).
- `schema_metatag` configurado para `Article` con `author` → `Person` (Andrés Rincón, cargo, URL LinkedIn como `sameAs`), `datePublished`, `image`, `headline`.
- En el tema, salida visible del autor y fecha en la página del artículo.

**Criterios de aceptación:**
- Los 3 artículos auditados (muestra de 2023 y 2024) emiten JSON-LD `Article` con `author.name` y `datePublished`.
- Rich Results test valida `Article` sin errores.
- La vista de artículo muestra byline autor+nombre y fecha legible.

**Módulo(s):** `schema_metatag`, temas, campos de contenido.
**Dependencias:** T-08 (metas por content type) y T-17 (estructura editorial) para los campos.

### T-15 · Schema `FAQPage` (FAQ de la home y nuevas páginas)

**Objetivo:** Convertir los bloques FAQ que ya existen en la home (§1.2.1, §2.4.3) en marcado `FAQPage` para ganar rich results y citabilidad GEO.

**Entregables:**
- Aplicación de JSON-LD `FAQPage` (vía `schema_metatag` con tipo FAQ asociado al contenido/paragraph o template) cubriendo los bloques "¿Qué es…?" de la home.
- Reutilización del patrón en TODO contenido futuro con preguntas (se garantiza por T-35).

**Criterios de aceptación:**
- `curl -s https://saibher.com | grep -c "FAQPage"` ≥ 1, con `mainEntity` (pregunta+respuesta) presentes.
- Rich Results Test: `FAQPage` válido sin errores.
- Las FAQs de servicio (POS) tienen respuesta directa en el `acceptedAnswer.text`.

**Módulo(s):** `schema_metatag`, tema (preprocess del nodo home).
**Dependencias:** T-13 (infraestructura de schema activa).

### T-16 · Schema `Service` / `LocalBusiness` en servicios

**Objetivo:** Dotar a las páginas de servicio (web a medida, Drupal, POS) de datos estructurados de oferta, como paso previo al esquema local cuando exista Google Business Profile (§2.4).

**Entregables:**
- `Service` en cada página de servicio: nombre, `provider` → `Organization`, `serviceType`, `areaServed` (Colombia/LatAm) y `offers` (rango de precio u "a consulta").
- Preparado el tipo `LocalBusiness`/`ProfessionalService` para activar cuando T-25 (GBP) esté listo.

**Criterios de aceptación:**
- Cada página de servicio emite JSON-LD `Service` válido (validado en Rich Results).
- No hay `Service` duplicado en páginas no de servicio.

**Módulo(s):** `schema_metatag`.
**Dependencias:** T-13.

---

## Fase B — Contenido

### T-17 · Estructura editorial del blog (campos, scheduler)

**Objetivo:** Habilitar la producción continua de contenido (plan §2.2) con la estructura semántica que requiere (autor, tema, fechas programadas).

**Entregables:**
- Campos en "articulo": `campo_tema` (referencia a taxonomía existente), autor explícito, "artículo actualizado" (fecha/frescura, para T-24/T-35).
- Flujo editorial: borrador → revisión → publicado programado con `scheduler`.
- Checklist de plantilla GEO (se formaliza en T-33) referenciada en `seo_checklist`.

**Criterios de aceptación:**
- Se crean 2 artículos de prueba con los campos completos y publicación programada a fechas distintas.
- `drush sched:list` (o equivalente) muestra las entradas programadas.
- Un artículo de prueba pasa el checklist de SEO del módulo `seo_checklist` sin críticos.

**Módulo(s):** campos de contenido, `scheduler`, `seo_checklist`.
**Dependencias:** ninguna (habilita B2+).

### T-18 · Publicar "Cuánto cuesta una página web a medida en Colombia"

**Objetivo:** Primera pieza "top of funnel" del calendario (§2.1-11, cluster web/precios) con respuestas directas citables.

**Entregables:**
- Artículo con: respuesta directa en ≤150 caracteres, rangos de precio (mín-máx actuales 2026), comparativa 3 escenarios, FAQ (4-6 preguntas) con `FAQPage`, enlaces internos a las 3 páginas de servicio y al form contacto.
- Image destacada optimizada (formato §T-12).

**Criterios de aceptación:**
- Publicado en `/blog/...`, con byline de autor (T-34) y JSON-LD `Article` + `FAQPage` válidos.
- Los primeros caracteres del contenido responden textualmente la pregunta.
- Al menos 3 enlaces internos salientes a páginas de servicio.

**Módulo(s):** Metatag/schema (T-13-15), editorial.
**Dependencias:** T-14, T-15 (schema), idealmente T-06 (URL).

### T-19 · Publicar "POS: SaaS vs licencia vitalicia"

**Objetivo:** Cubrir el diferenciador de negocio (§1.2.2) con contenido comparativo que posicione el modelo único de Saibher en las SERPs y conversaciones de IA.

**Entregables:**
- Artículo comparativo con tabla SaaS-mensualidad vs licencia vitalicia (con criterios de decisión por tipo de negocio), FAQ 4-6 preguntas con `FAQPage`, CTAs al form y a la página POS (T-21).
- Datos duros públicos (precios de mercado actualizados, sin inventar; se indica origen).

**Criterios de aceptación:**
- Publicado y con `Article` + `FAQPage` válidos.
- Incluye los términos objetivo: "punto de venta Colombia", "POS sin mensualidad", "licencia vitalicia".
- Enlaza a T-21 (página POS) cuando exista (o la marca la crea primero).

**Módulo(s):** editorial.
**Dependencias:** T-21 (página POS de destino) ideal; publicable antes si se enlaza a la home FAQ.

### T-20 · Calendario editorial 2 posts/mes (5 pilares)

**Objetivo:** Convertir el plan §2.2 en un calendario ejecutable de 6 meses (12 publicaciones) con asignación por persona.

**Entregables:**
- Documento/hoja de cálculo "calendario editorial": para cada mes, 2 artículos con título, pilar, keywords objetivo, autor asignado, fecha de publicación y estado.
- Primera ola de títulos concretos por pilar:
  - Pilar A (POS): "Qué es un sistema POS y cómo elegirlo en Colombia"; "POS para tienda/supermercado"; "Facturación electrónica DIAN y tu POS".
  - Pilar B (ERP a medida): "Software de gestión a medida vs SaaS: cuándo conviene"; "Cuánto cuesta un ERP para pyme en Colombia".
  - Pilar C (Drupal): "Drupal vs WordPress para pyme en Colombia"; "Migración a Drupal 11: pasos y costos".
  - Pilar D (web/conversión): "Web para restaurante"; "SEO para pymes 2026".
  - Pilar E (técnico, 1/mes): tutorial actual (Drupal 11, módulos).
- Definición en el calendario de qué artículo lleva FAQ/`FAQPage` obligatorio (todos los de pilares A-D).

**Criterios de aceptación:**
- El calendario cubre 12 espacios con autor y fecha y no deja 2 meses sin publicación.
- Cada pilar A-D tiene al menos 2 piezas en el semestre; pilar E al menos 6 tutoriales técnicos.
- 3 artículos del primer mes ya redactados y en revisión al cierre del trimestre.

**Módulo(s):** oficina/editorial (Spreadsheet o `scheduler`).
**Dependencias:** T-17, T-18, T-19.

### T-21 · Página de servicio POS dedicada

**Objetivo:** Cerrar el hueco §1.2.2: hoy el POS solo existe como nota de la home y una página con title en minúsculas y sin description (§1.1.4).

**Entregables:**
- Página `/servicios/sistema-punto-de-venta` (nuevo alias limpio, T-06) con: definición, modelo de licencia vitalicia, integraciones (inventario/tienda online), procesos DIAN, rondas de precios orientativos, FAQ 6-8 preguntas (`FAQPage`), testimonios cuando existan.
- Redirección 301 desde `/sistema-de-punto-de-ventas-0`.
- Title/metadesc optimizados (T-08) y `Service` schema (T-16).

**Criterios de aceptación:**
- La URL antigua → 301 a la nueva; la nueva tiene H1, title, description y `Service` + `FAQPage`.
- Es la página objetivo de T-19.
- Respuesta directa a "¿qué es?" en el primer bloque.

**Módulo(s):** editorial + Metatag/schema.
**Dependencias:** T-06, T-08, T-16.

### T-22 · Casos de éxito (3-4 proyectos)

**Objetivo:** Dar al portafolio casos medibles (§2.2) —hoy inexistente (§1.2.1)— para conversión y citabilidad.

**Entregables:**
- Páginas de caso (content type "proyecto") para 3-4 clientes reales con: reto, solución, resultado medible (nº, % o ahorro de tiempo si hay datos reales), tecnologías, testimonio.
- Vista `/portafolio` con fichas de caso enlazadas.
- Schema `Article`/`Service` por caso (o `Review` si hay testimonio formal).

**Criterios de aceptación:**
- 3-4 casos publicados con al menos un dato cuantitativo cada uno y sin componentes inventados (se indican los que son aproximados).
- `/portafolio` muestra las fichas; cada ficha enlaza al caso.
- LCP de las páginas de caso < 2,5 s (imágenes con T-12).

**Módulo(s):** content type + views (existentes en tema custom).
**Dependencias:** T-06, T-08, T-17.

### T-23 · Páginas de localización (Bogotá, Villavicencio/Meta, Colombia)

**Objetivo:** Atacar la intención local (plan §2.2) y sentar base para el esquema local con GBP (T-25).

**Entregables:**
- 3 páginas de servicio localizado: Bogotá, Villavicencio/Meta y Colombia (nacional), con: propuesta adaptada, ejemplos de sectores locales, contacto.
- Cada una con title/description y `Service` (areaServed).

**Criterios de aceptación:**
- Las 3 páginas existan, indexables, sin duplicar el texto literal de la página de servicio nacional.
- Se enlazan desde la home/footer y mutuamente (poca canibalización).

**Módulo(s):** editorial.
**Dependencias:** T-08, T-25 (para la parte NAP si se lanza en paralelo).

### T-24 · Frescar artículos de 2023-2024

**Objetivo:** Atender la señal de frescura (§1.2.1, §2.4-6): los posts técnicos con >1 año pierden fuerza para IA y SERPs.

**Entregables:**
- Selección de los 5 posts con mejor señal actual (GSC) y actualización: datos/versiones 2026, fecha de "última actualización" visible, respuesta directa al inicio, FAQ breve.
- Updates en `datePublished`/`dateModified` (T-14/T-17).

**Criterios de aceptación:**
- Los 5 posts de la muestra tienen fecha modificada 2026 visible y FAQ/respuesta directa.
- Sin perder ninguna URL (no se cambian aliases).

**Módulo(s):** editorial, campos de fecha.
**Dependencias:** T-14 (Article), T-17.

---

## Fase C — Autoridad / backlinks (no técnico, coordinado)

### T-25 · Crear/reclamar Google Business Profile

**Objetivo:** Cerrar el gap local crítico (§1.3.3): no existe ficha ni NAP; requisito para el esquema `LocalBusiness` y reseñas.

**Entregables:**
- Perfil de Google Business creado/reclamado (Bogotá; sucursal Villavicencio si aplica) con categoría, NAP, teléfono, horario, 5+ fotos, URL correcta saibher.com.
- Enlace de GBP desde el footer del sitio.

**Criterios de aceptación:**
- La ficha es verificable y pública; categoría "Desarrollador de software"/"Agencia de marketing digital".
- Buscar "Saibher software Bogotá" devuelve la ficha con enlace al sitio.
- NAP idéntico en ficha, footer y directorios (T-36/T-29).

**Módulo(s)/herramienta(s):** Google Business Profile (externo).
**Dependencias:** T-02 (URL canónica). Precede a T-16 (`LocalBusiness`).

### T-26 · Crear LinkedIn Company Saibher

**Objetivo:** Convertir la presencia personal (solo perfil de Andrés, §1.3.2) en página de empresa B2B con backlink.

**Entregables:**
- Página Company "Saibher" con descripción, URL del sitio, logo, industry, city, y vinculación de Andrés como admin.
- 3 primeras publicaciones iniciales (casos de éxito T-22 / artículos T-18/19).

**Criterios de aceptación:**
- La página Company existe y enlaza a saibher.com.
- El perfil personal de Andrés la cita como empresa actual.

**Módulo(s)/herramienta(s):** LinkedIn.
**Dependencias:** T-22 (contenido para postear).

### T-27 · Completar Drupal.org + directorio de agencias

**Objetivo:** Maximizar el activo de autoridad #1 (§1.3.1, §2.3): el perfil Drupal.org.

**Entregables:**
- Perfil `drupal.org/u/saibher` completo: foto, bio, org, website, región.
- Solicitud de alta en el **directorio de agencias/proveedores** de Drupal.org.
- Vincular el issue de core reportado (2025) en el perfil como actividad.

**Criterios de aceptación:**
- El perfil es público con website y bios completas.
- La solicitud de agencia está enviada (se deja constancia de fecha).

**Módulo(s)/herramienta(s):** Drupal.org.
**Dependencias:** ninguno.

### T-28 · Comunidad Drupal Colombia + DrupalCon

**Objetivo:** Generar menciones y enlaces contextuales (§2.3-4): eventos y comunidad.

**Entregables:**
- Inscripción/participación en la Asociación Drupal Colombia (`drupal.org/project/drupalcolombia`) y canal @drupalco.
- Propuesta de charla o taller (tema técnico elegido del pilar E) para el siguiente Drupal Camp Colombia / DrupalCon LATAM; bio con enlace al sitio en el programa.

**Criterios de aceptación:**
- Participación activa registrada (al menos 1 evento o 1 content colaboración en el semestre).
- Si hay charla aceptada: bio pública con enlace a saibher.com.

**Módulo(s)/herramienta(s):** Drupal.org, YouTube @drupalco.
**Dependencias:** T-27 (perfil).

### T-29 · Directorios de agencias

**Objetivo:** Primeros backlinks fuera de perfiles propios (§1.3.1) con NAP consistente.

**Entregables:**
- Registro/verificación en: Clutch, GoodFirms, DesignRush y Sortlist; en Colombia: Cámara de Comercio (informe comercial) / Informa Colombia (NAP).
- Ficha idéntica (descripción corta, categorías, URL, ciudad) en cada uno.

**Criterios de aceptación:**
- ≥ 3 directorios con perfil publicado y enlace a saibher.com.
- NAP idéntico en todos (nombre, dirección, URL, teléfono) — verificación T-36.

**Módulo(s)/herramienta(s):** plataformas externas.
**Dependencias:** T-25 (NAP oficial definido).

### T-30 · Outreach y publicaciones invitadas

**Objetivo:** Conseguir backlinks editoriales (§2.3-6): pitch de los tutoriales técnicos existentes (p. ej. migración Drupal 9.5→10.2) a medios especializados.

**Entregables:**
- Lista de 10+ medios con correo/contacto: The Drop Times, Drupal Weekly, grupos Drupal LATAM, blogs PHP/ILT, newsletters.
- Plantilla de pitch por medio (tema + muestra de contenido ya publicado por Saibher).
- 2-3 "colaboraciones" pactadas por trimestre.

**Criterios de aceptación:**
- ≥ 2 publicaciones en medios externos en el trimestre con enlace al sitio (dofollow o UGC, se documenta).
- Cada una tiene CTA correcto (conversión, no solo tráfico).

**Módulo(s)/herramienta(s):** outreach manual.
**Dependencias:** T-24 (contenido actualizado para pitchear).

### T-31 · Unificar marca y GitHub

**Objetivo:** Resolver la dispersión de identidad saibher/technomundohmr (§1.3.2) que divide la equidad.

**Entregables:**
- Definición de marca única (recomendado: "Saibher") para GitHub, Teachlr y cualquier cuenta pública.
- Repos `saibher`/`saibher_bms` con README profesional y enlace + descripción al sitio.
- Dejar que el perfil GitHub pase referencias (favicons, links).

**Criterios de aceptación:**
- GitHub muestre "Saibher" (no tecnik `technomundohmr` como marca principal en display).
- README de los 2 repos incluye website y cases de contacto.

**Módulo(s)/herramienta(s):** GitHub.
**Dependencias:** ninguno.

### T-32 · PR digital con datos originales

**Objetivo:** Generar citas de terceros (prensa TI/negocios colombiana) con datos propios (§2.3-8).

**Entregables:**
- Micro-encuesta o dataset propio (p. ej. "X% de pequeños comercios colombianos usan Excel para su inventario") con metodología documentada.
- Nota de prensa y contactos de 15 medios/periodistas de tecnología y emprendimiento en Colombia.

**Criterios de aceptación:**
- 1-2 piezas de prensa al año con mención y enlace a saibher.com.
- Los datos publicados tienen fuente y fecha.

**Módulo(s)/herramienta(s):** externo.
**Dependencias:** T-22 (datos de casos para el storytelling).

---

## Fase D — GEO (transversal)

### T-33 · Plantilla de "respuesta directa" (guía editorial)

**Objetivo:** Formalizar el patrón §2.4-2: cada contenido contesta su pregunta objetivo en los primeros 100-150 caracteres (patrón citable por IA).

**Entregables:**
- Guía editorial interna (página de documentación) con: estructura de "respuesta directa", reglas de H2-pregunta, longitud, cuándo usar FAQ.
- Checklist aplicado por defecto en `seo_checklist` (T-17).

**Criterios de aceptación:**
- La guía existe y es accesible al equipo.
- Los 3 artículos siguientes publicados (T-18/19 y 1 de T-20) cumplen la regla de primeros 150 caracteres.

**Módulo(s):** editorial (doc interna).
**Dependencias:** T-17; precede a T-35.

### T-34 · Autoría visible en todos los artículos

**Objetivo:** Consolidar la entidad "Andrés Rincón / Saibher" (§2.4-1, T-14): byline visible + bio + enlaces.

**Entregables:**
- Salida visual en la vista de artículo: autor (nombre + cargo + 1 línea bio) y fecha.
- Enlace del byline a LinkedIn (perfil §1.3) como `sameAs`.
- Logo/foto @ Image en el byline (optimizada per T-12).

**Criterios de aceptación:**
- 100% de los artículos muestran byline con autor+fecha.
- El HTML del artículo contiene el enlace al perfil LinkedIn dentro del contexto del autor.

**Módulo(s):** tema `saibher`.
**Dependencias:** T-14 (Article/Person), T-17 (campo autor).

### T-35 · Preguntas PAA + FAQ en cada artículo

**Objetivo:** Alinear cada publicación con "People Also Ask" y la forma de preguntar de los usuarios de IA (§2.4-3).

**Entregables:**
- En el flujo editorial (T-17/T-33): obligatorio que cada artículo pilares A-D tenga 4-6 preguntas FAQ con `FAQPage` schema y subtítulos H2 en formato pregunta.
- Revisión trimestral de los "PAA" de las 25 keywords objetivo para alimentar la backlog editorial.

**Criterios de aceptación:**
- Los artículos publicados en pilares A-D incluyen magic `FAQPage` válido (validado en Rich Results).
- Los H2 preguntas coinciden con consultas reales (muestra de GSC/search).

**Módulo(s):** `schema_metatag`, editorial.
**Dependencias:** T-15, T-33.

### T-36 · Entidad y NAP consistente

**Objetivo:** Que todas las propiedades de la marca compartan la misma entidad (web, GBP, LinkedIn, directorios, perfil Drupal) para resolución inequívoca (§2.4-1, T-25/T-29).

**Entregables:**
- Hoja de "directorio de marca": nombre, descripción corta, NAP, teléfono, URL en cada plataforma.
- `Organization` schema con `sameAs` completo (T-13) actualizado tras cada alta nueva.

**Criterios de aceptación:**
- La hoja de marca lista ≥ 8 propiedades con datos idénticos.
- `sameAs` en el JSON-LD de la home apunta exclusivamente a perfiles verificados y activos.

**Módulo(s):** `schema_metatag`, doc.
**Dependencias:** T-25, T-29, T-31 (para consolidar antes).

### T-37 · Sonda de citabilidad mensual en IA

**Objetivo:** Medir el avance GEO (§2.5 "Menciones IA") con método reproducible.

**Entregables:**
- Script/plantilla de 5 consultas objetivo (p. ej. "mejores agencias Drupal en Colombia", "qué es un POS sin mensualidad", "cuánto cuesta una web a medida en Colombia") probadas en: ChatGPT (respuesta web con fuentes), Perplexity, Copilot y AI Overviews.
- Registro mensual: ¿saibher.com aparece como fuente? (URL citada, contexto).
- Paso de la plantilla a un archivo (CSV/Google Sheet) vinculable al panel T-41.

**Criterios de aceptación:**
- 1 sondaje documentado por mes con capturas/links.
- Meta a 12 meses: ≥2 de 5 consultas citando a saibher.com (KPI §2.5).

**Módulo(s)/herramienta(s):** externo (consultas), hoja de cálculo.
**Dependencias:** T-33-T-36 (para que los resultados mejoren).

### T-38 · Auditar ruta "Sitio Suspendido" y páginas huérfanas

**Objetivo:** Investigar el fragmento indexado "Sitio Suspendido" detectado (§1.2.2) y cualquier página huérfana caída que pueda perjudicar la entidad.

**Entregables:**
- Identificación de las rutas afectadas (GSC + búsqueda site:saibher.com) y causa (hosting/redirect/template roto).
- Corrección o 301 a página equivalente; alta de páginas 404 reales en `search404`.

**Criterios de aceptación:**
- `site:saibher.com` no muestra ningún resultado con "Sitio Suspendido".
- Cobertura en GSC: sin "404 suaves" ni huérfanas relevantes.

**Módulo(s)/herramienta(s):** `search404`, GSC.
**Dependencias:** acceso a GSC (T-40).

---

## Fase E — Métricas y seguimiento

### T-39 · GA4 consolidado + eventos de conversión

**Objetivo:** Corregir el doble contenedor GA4 detectado (§1.1.1, G-8C5MY41CR4 y G-SS308LYJ1J) y medir conversiones.

**Entregables:**
- Un solo contenedor `google_tag` en produção (eliminar el segundo ID).
- Eventos: `form_contact_submit`, `click_asesoria` (botón "Necesito asesoría"), `newsletter_subscribe`; meta `conversion` en GA4 para estos.

**Criterios de aceptación:**
- El HTML del sitio muestra 1 único `gtag("config", …)`.
- En GA4 "Conversions", los 3 eventos aparecen y recibieron el primer hit en el mes siguiente a configurar.

**Módulo(s):** `google_tag`, temas.
**Dependencias:** ninguno.

### T-40 · Verificar GSC + Bing y enviar sitemap

**Objetivo:** Dar de alta las propiedades de búsqueda (§2.5) y exponer el sitemap útil de T-03.

**Entregables:**
- Propiedad de dominio (DNS) en Google Search Console + envío de `sitemap.xml`.
- Alta en Bing Webmaster Tools (importando desde GSC).
- Solicitud de indexación de las URLs principales.

**Criterios de aceptación:**
- `sitemap.xml` con estado "Éxito" en GSC; recrawleo de las páginas claves.
- Bing WMT con propiedad verificada.

**Módulo(s)/herramienta(s):** GSC, Bing WMT.
**Dependencias:** T-03 (sitemap), T-02 (canonical) — para verificar sobre la URL final.

### T-41 · Línea base y panel de keywords

**Objetivo:** Registrar el "antes" (informe Excel `Informe-SEO-Saibher.xlsx`) y crear el panel de medición.

**Entregables:**
- Export de GSC/GA4 a la línea base del informe Excel existente (hojas Metricas).
- Panel de seguimiento de las 25 keywords objetivo (posiciones e impresiones) con cadencia mensual.

**Criterios de aceptación:**
- La hoja "Metricas" del Excel tiene valores basales correspondientes a GSC/GA4 (no solo 0) y fuentes anotadas.
- El panel lista 25 keywords con posición//impresión/CTR iniciales.

**Módulo(s)/herramienta(s):** GSC, Excel/Google Sheets, rank-tracker.
**Dependencias:** T-39, T-40.

### T-42 · Monitor de Core Web Vitals mensual

**Objetivo:** Medir la evolución de las métricas de §1.1.2 (LCP, CLS, INP, TTFB) con señal de campo (CrUX/PSI).

**Entregables:**
- Query de PSI/CrUX para home y 5 URLs claves, guardada en el panel (T-41).
- Revisión mensual de ≥ "bueno" y de la mejora del consumo de caché (T-10/11/12).

**Criterios de aceptación:**
- Mes a mes queda registro de LCP/CLS/INP/TTFB por URL y pase de obra a "bueno" en fondo (CrUX 75 pct).
- Alertas cuando una URL degrada (registro en el reporte mensual T-43).

**Módulo(s)/herramienta(s):** PSI API/CrUX, panel.
**Dependencias:** T-40, T-12 (para que empiece a mejorar).

### T-43 · Proceso de reporte mensual

**Objetivo:** Cerrar el bucle de mejora: revisar métricas, prioridades y próximas tareas cada mes.

**Entregables:**
- Plantilla de reporte mensual: métricas del panel, avances de tareas A-D, retos detectados, próximos pasos.
- Reunión de revisión (≤30-45 min) con propietarios.

**Criterios de aceptación:**
- Reporte mensual disponible con las métricas de T-41/T-42/T-37 y el estado de las tareas de este documento.
- Cada reporte incluye al menos 1 decisión/ajuste al plan (con responsable).

**Módulo(s)/herramienta(s):** proceso.
**Dependencias:** T-41, T-42, T-37, T-43.

---

> **Nota de cierre.** Este desglose no se ejecuta en esta fase: es la documentación de trabajo. Cada tarea debe moverse a un tracker (issue/card) y cerrarse contra sus **criterios de aceptación**. Las tareas marcadas P0 (T-01, T-02, T-03, T-04, T-08, T-10, T-12, T-13, T-14, T-15, T-40) son las primeras 4 semanas; el resto siguen el orden de fases.

---

*Documento generado el 25/09/2026. Los datos de velocidad corresponden a medición lab propia (navegador controlado); los datos de campo requieren Search Console/PageSpeed Insights. Las afirmaciones sin verificación directa se marcan como ESTIMADO o NO VERIFICADO en el texto.*