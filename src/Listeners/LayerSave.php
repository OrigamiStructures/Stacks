<?php


namespace Stacks\Listeners;


use Cake\Event\Event;
use DebugKit\Model\Table\PanelsTable;

class LayerSave implements \Cake\Event\EventListenerInterface
{

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        $eventMap = [
            'Model.afterSave' => 'afterSave',
            'Model.afterSaveCommit' => 'afterSaveCommit',
        ];
        return $eventMap;
    }

    /**
     * @param $event Event
     * @param $entity
     * @param $options
     */
    public function afterSave($event, $entity, $options)
    {
        if (! in_array($event->getSubject()->getAlias(), ['Panels', 'Requests', 'Preferences'])) {
//            osd($event);
            osd($entity);
            osd($options);
//            die;
        }
    }

    /**
     * @param $event Event
     * @param $entity
     * @param $options
     */
    public function afterSaveCommit($event, $entity, $options)
    {
        if (! in_array($event->getSubject()->getAlias(), ['Panels', 'Requests', 'Preferences'])) {
            osd('COMMIT!');
//            osd($event);
            osd($entity);
            osd($options);
//            die;
        }
    }

}
