<?php

namespace Stacks\Listeners;

use Cake\Cache\Cache;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use DebugKit\Model\Table\PanelsTable;
use Stacks\Constants\CacheCon;
use Stacks\Model\Table\StacksTable;
use function collection;

/**
 * Listener Class to keep stack entity cache data up to date
 *
 * Cached Stack entities contain large amounts of related data. While this is a convenience
 * when dealing with complex objects, it can cause problems when a single entity changes.
 * That entity might be part of the context data for several different kinds of Stacks or
 * part of several of the same kind of stack.
 *
 * So a system is required to watch for CUD actions that impact the contents of a stack.
 *
 * Each stack table is configured with an array that maps its various layers to the
 * name of some table class; the class that is ultimately responsible for that
 * layers data.
 *
 * These maps might present simple parings:
 *
 * <pre>
 * _________________________________________________
 * | layer               |table                    |
 * -------------------------------------------------
 * | people              | people                  |
 * | address             | addresses               |
 * -------------------------------------------------
 * </pre>
 *
 * Or it might show what table locally aliased layers arise from
 *
 * <pre>
 * _________________________________________________
 * | layer               |table                    |
 * -------------------------------------------------
 * | day_shift           | people                  |
 * | night_shift         | people                  |
 * -------------------------------------------------
 * </pre>
 *
 * If we could gather all these mappings into one place and identify
 * which stack table the pares belong in, we would have a basis for
 * understanding the potential impact of a change in any given record type.
 *
 * That's exactly what this listener accomplishes.
 *
 * It insures the existence this master map, then based on an
 * entity that has change, it determines the table underlying the
 * entity and uses that information to determine which stacks contain
 * layers that derive from that table. That's where the map comes in!
 *
 * Once the list of potential intersections are found, the process shifts
 * to checking each stack to see if the change entity specifically is, was,
 * or will be involved in some stack (identified by its root id).
 *
 * Whew! Let's get started.
 *
 * @package Stacks\Listeners
 */
class LayerSave implements \Cake\Event\EventListenerInterface
{

    /**
     * First we need to make sure the listener is listening.
     *
     * Application.php will register this listener so it'll
     * be on all the time.
     *
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        $eventMap = [
            'Model.afterSaveCommit' => 'afterSaveCommit',
        ];
        return $eventMap;
    }



    /**
     * And here is our ear-to-the-ground
     *
     * There are a few save activities that don't have anything to do with
     * Stack Table though so we need a guard to filter out the noise.
     *
     * Then, We read the cached map that links tables to their various layer incarnations.
     * If the cache isn't available, we'll take a little detour to construct it.
     *
     * At this point, we have the entity that changed (where we can read the id of the record),
     * the name of the table responsible for the entity, and the map with table names as the
     * first level key. It's a simple matter then to get the sub-map describing the role
     * of this table in the currently configured stacks
     * 
     * @param $event Event
     * @param $entity Entity
     * @param $options
     * @noinspection PhpUnused
     */
    public function afterSaveCommit($event, $entity, $options)
    {
        if (! in_array($event->getSubject()->getAlias(), ['Panels', 'Requests', 'Preferences'])) {
            $map = Cache::read(CacheCon::SCKEY, CacheCon::SCCONFIG) ?? $this->compileLayerMap();
            $table = $entity->getSource();
            foreach (Hash::get($map, strtolower($table)) as $stackName => $layerNames) {
                $this->expireStackCaches($stackName, $entity->id, $layerNames);
            }
        }
    }

    protected function compileLayerMap()
    {
        $tableDir = new Folder(APP.'Model'.DS.'Table');
        $stackTableList = ($tableDir->find('(.*)StackTable.php'));
        $classList = collection($stackTableList)
            ->map(function ($filename){
                $alias = str_replace('Table.php', '', $filename);
                TableRegistry::getTableLocator()->get($alias)->compileLayerMapFragment();
                TableRegistry::getTableLocator()->remove($alias);
            })->toArray();
        return Cache::read(CacheCon::SCKEY, CacheCon::SCCONFIG);
    }
    /**
     * @param $stackName string
     * @param $id int|string
     * @param $layerNames array
     */
    protected function expireStackCaches($stackName, $id, $layerNames)
    {
        /**
         * @var StacksTable $stackTable
         */
        $stackTable = TableRegistry::getTableLocator()->get($stackName);
        foreach ($layerNames as $layerName) {
            foreach ($stackTable->distillFromGivenSeed($layerName, [$id])->toArray() as $entity) {
                $stackTable->deleteCache($entity->id);
            }
        }
    }


}
