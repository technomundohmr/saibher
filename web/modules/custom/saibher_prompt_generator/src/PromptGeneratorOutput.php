<?php

namespace Drupal\saibher_prompt_generator;

class PromptGeneratorOutput {
  
  public function getOutputGeneralData() {
    $output = [
        '' => '-sin especificar formato-',
        'Texto plano sin formato' => 'Texto plano sin formato',
        'Lista númerica' => 'Lista númerica',
        'Introducción, cuerpo y conclusión' => 'Introducción, cuerpo y conclusión',
        'Paso a paso' => 'Paso a paso',
        'En parrafos sin formato' => 'En parrafos sin formato',
        'Tutorial' => 'Tutorial',
    ];
    return $output;
  }
  
  public function getOutputCode() {
    $output = [
        '' => '-sin especificar formato-',
        'En un solo documento de código' => 'En un solo documento de código',
        'separado por archivos' => 'separado por archivos',
        'separado por líneas de código' => 'separado por líneas de código',
        'separado por porciones de código' => 'separado por porciones de código',
    ];
    return $output;
  }
  
  public function getOutputCodeAccuracy() {
    $output = [
        '' => '-sin especificar formato-',
        'Código explicado de manera sencilla' => 'Código explicado de manera sencilla',
        'Evita comentar el código' => 'Evita comentar el código',
        'Código comentado y explicado' => 'Código comentado y explicado',
        'Codigo limpio sin explicación ni comentarios' => 'Codigo limpio sin explicación ni comentarios',
    ];
    return $output;
  }

}
