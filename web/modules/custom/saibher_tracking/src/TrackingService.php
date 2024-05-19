<?php

namespace Drupal\saibher_tracking;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TrackingService implements ContainerInjectionInterface{

    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entity_type_manager) {
        $this->entityTypeManager = $entity_type_manager;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    public function newTracking($word, $origin) {
        $tracking_storage = $this->entityTypeManager->getStorage('saibher_tracking_tracking')->loadByProperties([
            'field_searched_term' => $word,
            'field_origin' => $origin,
        ]);
        if($tracking_storage == []){
            $tracking_storage = $this->entityTypeManager->getStorage('saibher_tracking_tracking')->create([
                'name' => $word,
                'field_searched_term' => $word,
                'field_origin' => $origin,
                'field_times' => 1
            ]);
            $tracking_storage->save();
        }else{
            foreach ($tracking_storage as $key => $value) {
                $last_tracking = $value->field_times->value;
                $last_tracking = $last_tracking + 1;
                $value->set('field_times', $last_tracking);
                $value->save();
            }
        }

        return $tracking_storage;
    }
}
