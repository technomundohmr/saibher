<?php

namespace Drupal\saibher_seo;

/**
 * Optimiza las imagenes en linea del HTML ya renderizado (T-12).
 *
 * Se aplica por evento de respuesta y no por #post_render en un preprocess,
 * porque ThemeManager::render() recibe $variables por valor: lo que un preprocess
 * agregue a $variables nunca llega al render array, y #post_render nunca se
 * invoca. El evento kernel.response si ve el documento completo.
 *
 * Cada etiqueta <img> se reescribe por separado con expresiones regulares y el
 * resto del documento queda intacto byte a byte. DOMDocument seria mas correcto
 * en teoria, pero su parser reescribe el documento (doctype, tbody, atributos
 * personalizados) y eso puede romper el markup del tema.
 */
class ImageOptimizer {

  /**
   * Ancho maximo del derivado que se sirve.
   */
  const MAX_WIDTH = 1280;

  /**
   * El estilo de imagen que genera el derivado.
   */
  const STYLE_ID = 'saibher_content';

  /**
   * Cuantos caracteres seLeave de HTML sin tocar antes de rendirse.
   *
   * Evita que un HTML patologico se procese durante tiempo indefinido. El limite
   * esta muy por encima del tamano de la pagina mas grande del sitio.
   */
  const SIZE_LIMIT = 3000000;

  /**
   * Optimiza las imagenes de un documento HTML.
   *
   * @param string $html
   *   El documento renderizado.
   *
   * @return string
   *   El documento con las imagenes optimizadas.
   */
  public function optimize(string $html): string {
    if (strpos($html, '<img') === FALSE) {
      return $html;
    }

    if (strlen($html) > self::SIZE_LIMIT) {
      return $html;
    }

    $is_first = TRUE;
    $result = preg_replace_callback(
      '#<img\b[^>]*>#i',
      function (array $match) use (&$is_first): string {
        $optimized = $this->optimizeTag($match[0], $is_first);
        $is_first = FALSE;
        return $optimized;
      },
      $html
    );

    return $result ?? $html;
  }

  /**
   * Optimiza una etiqueta <img> suelta.
   *
   * Reglas conservadoras a proposito:
   * - Solo se anaden width/height si la etiqueta NO tiene class, porque el CSS
   *   compilado no trae una regla global img { max-width: 100%; height: auto; }.
   *   Sin height:auto, un atributo height junto a una clase que fija el ancho
   *   (por ejemplo w-full en el logo) deforma la imagen.
   * - loading y decoding si se anaden siempre: no afectan al dimensionado.
   * - El src se apunta al derivado de MAX_WIDTH si el original es mas ancho. Los
   *   SVG y los data URI se dejan intactos.
   *
   * @param string $tag
   *   La etiqueta <img> original.
   * @param bool $is_first
   *   Si es la primera imagen del documento (no se marca como lazy).
   *
   * @return string
   *   La etiqueta optimizada, o la original si no se puede mejorar.
   */
  private function optimizeTag(string $tag, bool $is_first): string {
    $attributes = $this->parseAttributes($tag);
    if ($attributes === NULL) {
      return $tag;
    }

    $src = $attributes['src'] ?? '';
    if ($src === '' || str_starts_with($src, 'data:')) {
      return $tag;
    }

    $changed = FALSE;

    // Una imagen puede venir apuntando a un estilo de imagen del sitio. Solo se
    // interviene el de este modulo: los demas (thumbnail, medium, xl...) son una
    // decision deliberada del sitio y sus imagenes son mas pequenas que el tope
    // de MAX_WIDTH, asi que sustituirlas solo las agrandaria. Si en cambio apunta
    // a un derivado de saibher_content que ya no esta en disco, se deshace el
    // estilo para volver al original y que la imagen deje de estar rota.
    $reference = $this->styleReference($src);
    if ($reference !== NULL && $reference['style'] === self::STYLE_ID) {
      $attributes['src'] = $reference['original'];
      $src = $reference['original'];
      $changed = TRUE;
    }
    elseif ($reference !== NULL) {
      return $this->renderTag($tag, $attributes, $this->addLazyAttributes($attributes, $is_first, $changed));
    }

    $size = $this->imageSize($src);
    if ($size === NULL) {
      return $this->renderTag($tag, $attributes, $changed);
    }
    [$width, $height] = $size;

    if ($width > self::MAX_WIDTH) {
      $derivative = $this->imageStyleUrl($src);
      if ($derivative !== NULL) {
        $attributes['src'] = $derivative;
        $scale = min(self::MAX_WIDTH / $width, self::MAX_WIDTH / $height);
        $width = (int) round($width * $scale);
        $height = (int) round($height * $scale);
        $changed = TRUE;
      }
    }

    if (!isset($attributes['width']) && !isset($attributes['class'])) {
      $attributes['width'] = (string) $width;
      $attributes['height'] = (string) $height;
      $changed = TRUE;
    }

    $changed = $this->addLazyAttributes($attributes, $is_first, $changed);

    return $this->renderTag($tag, $attributes, $changed);
  }

  /**
   * Anade loading y decoding, que son seguros en cualquier imagen.
   *
   * @param array $attributes
   *   Los atributos de la etiqueta.
   * @param bool $is_first
   *   Si es la primera imagen del documento.
   * @param bool $changed
   *   Si ya se modifico algún atributo antes.
   *
   * @return bool
   *   Si se modifico algún atributo.
   */
  private function addLazyAttributes(array &$attributes, bool $is_first, bool $changed): bool {
    if (!isset($attributes['loading'])) {
      $attributes['loading'] = $is_first ? 'eager' : 'lazy';
      $changed = TRUE;
    }

    if (!isset($attributes['decoding'])) {
      $attributes['decoding'] = 'async';
      $changed = TRUE;
    }

    return $changed;
  }

  /**
   * Reconstruye la etiqueta con los atributos calculados.
   *
   * @param string $tag
   *   La etiqueta original, que se devuelve tal cual si nada cambio.
   * @param array $attributes
   *   Los atributos resultantes.
   * @param bool $changed
   *   Si alguno de los atributos cambio.
   *
   * @return string
   *   La etiqueta final.
   */
  private function renderTag(string $tag, array $attributes, bool $changed): string {
    if (!$changed) {
      return $tag;
    }

    $rendered = '';
    foreach ($attributes as $name => $value) {
      $rendered .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_HTML5) . '"';
    }

    return '<img' . $rendered . '>';
  }

  /**
   * Extrae los atributos de una etiqueta.
   *
   * @param string $tag
   *   La etiqueta.
   *
   * @return array|null
   *   Los atributos, o NULL si la etiqueta no se pudo interpretar.
   */
  private function parseAttributes(string $tag): ?array {
    $pattern = '#([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'>]+)#';
    if (!preg_match_all($pattern, $tag, $matches, PREG_SET_ORDER)) {
      return NULL;
    }

    $attributes = [];
    foreach ($matches as $match) {
      $value = $match[2];
      $quote = $value[0] ?? '';
      if ($quote === '"' || $quote === "'") {
        $value = substr($value, 1, -1);
      }
      $attributes[$match[1]] = $value;
    }

    return $attributes;
  }

  /**
   * Detecta si una URL apunta a un derivado de estilo y devuelve el original.
   *
   * @param string $src
   *   La URL del atributo src.
   *
   * @return array|null
   *   ['style' => id del estilo, 'original' => URL del archivo original], o NULL si
   *   la URL no es un derivado de un archivo local.
   */
  private function styleReference(string $src): ?array {
    $path = parse_url($src, PHP_URL_PATH);
    if (!is_string($path)) {
      return NULL;
    }

    $prefix = base_path() . 'sites/default/files/';
    if (!str_starts_with($path, $prefix)) {
      return NULL;
    }

    $rest = substr($path, strlen($prefix));
    if (!preg_match('#^styles/([^/]+)/(?:public|private)/(.+)$#', $rest, $matches)) {
      return NULL;
    }

    return [
      'style' => $matches[1],
      'original' => $prefix . $matches[2],
    ];
  }

  /**
   * Traduce una URL publica de un archivo a su URI con esquema public://.
   *
   * @param string $src
   *   La URL publica del archivo.
   *
   * @return string|null
   *   La URI public://, o NULL si la URL no apunta al directorio publico.
   */
  private function publicUri(string $src): ?string {
    $path = parse_url($src, PHP_URL_PATH) ?: '';
    $prefix = base_path() . 'sites/default/files/';
    if (!str_starts_with($path, $prefix)) {
      return NULL;
    }

    return 'public://' . rawurldecode(substr($path, strlen($prefix)));
  }

  /**
   * Resuelve las dimensiones reales de una imagen a partir de su URL publica.
   *
   * @param string $src
   *   La URL publica del archivo.
   *
   * @return array|null
   *   [ancho, alto], o NULL si no es una imagen local que se pueda medir.
   */
  private function imageSize(string $src): ?array {
    $uri = $this->publicUri($src);
    if ($uri === NULL) {
      return NULL;
    }

    $realpath = \Drupal::service('file_system')->realpath($uri);
    if ($realpath === FALSE || !is_file($realpath)) {
      return NULL;
    }

    // SVG y formatos sin soporte de GD no tienen dimensiones utiles aqui.
    $extension = strtolower(pathinfo($realpath, PATHINFO_EXTENSION));
    if (in_array($extension, ['svg', 'svgz'], TRUE)) {
      return NULL;
    }

    $info = @getimagesize($realpath);
    if ($info === FALSE || empty($info[0]) || empty($info[1])) {
      return NULL;
    }

    return [(int) $info[0], (int) $info[1]];
  }

  /**
   * Traduce una URL publica a la URL del derivado del estilo de contenido.
   *
   * Los derivados de archivos publicos no se sirven por una ruta de Drupal: los
   * entrega el servidor web directamente desde sites/default/files/styles. Por eso
   * no basta con reescribir el src; hay que garantizar que el archivo exista, o el
   * navegador se llevaria un 404. Si no se puede generar, se devuelve NULL y la
   * etiqueta conserva su src original.
   *
   * @param string $src
   *   La URL publica del archivo original.
   *
   * @return string|null
   *   La URL relativa del derivado, o NULL si no se puede generar.
   */
  private function imageStyleUrl(string $src): ?string {
    $uri = $this->publicUri($src);
    if ($uri === NULL) {
      return NULL;
    }

    $file_system = \Drupal::service('file_system');
    if ($file_system->realpath($uri) === FALSE) {
      return NULL;
    }

    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load(self::STYLE_ID);
    if ($style === NULL) {
      return NULL;
    }

    // buildUrl() devuelve un string en Drupal 11.3 y un Url en versiones
    // anteriores, asi que se cubren las dos firmas.
    try {
      $style_url = $style->buildUrl($uri);
    }
    catch (\Throwable $e) {
      return NULL;
    }

    if ($style_url instanceof \Drupal\Core\Url) {
      if (!$style_url->access()) {
        return NULL;
      }
      $style_url = $style_url->toString();
    }

    if (!is_string($style_url) || $style_url === '') {
      return NULL;
    }

    // Asegurar que el derivado exista en disco antes de anunciarlo en el HTML.
    // El chequeo usa is_file() y no solo realpath(): el realpath de Drupal pasa
    // por la cache del bin 'file.realpath', que puede devolver la ruta de un
    // archivo que ya no existe y hacer que se salte la generacion.
    $derivative_uri = $style->buildUri($uri);
    $derivative_path = $file_system->realpath($derivative_uri);
    if ($derivative_path === FALSE || !is_file($derivative_path)) {
      if (!$style->createDerivative($uri, $derivative_uri)) {
        return NULL;
      }
    }

    // URL relativa al dominio: mantiene el HTML portable entre entornos y evita
    // repetir el host en cada imagen.
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . base_path();
    if (str_starts_with($style_url, $base_url)) {
      $style_url = '/' . ltrim(substr($style_url, strlen($base_url)), '/');
    }

    return $style_url;
  }

}