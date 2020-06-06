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

class LayerSave implements \Cake\Event\EventListenerInterface
{

    /**
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
     * @param $event Event
     * @param $entity Entity
     * @param $options
     */
    public function afterSaveCommit($event, $entity, $options)
    {
        if (! in_array($event->getSubject()->getAlias(), ['Panels', 'Requests', 'Preferences'])) {
            osd('COMMIT!');
//            osd($event);
            osd($entity);
            $map = Cache::read(CacheCon::SCKEY, CacheCon::SCCONFIG) ?? $this->compileLayerMap();
            $table = $entity->getSource();
            osd($table);
            osd(Hash::get($map, strtolower($table)));
            foreach (Hash::get($map, strtolower($table)) as $stackName => $layerNames) {
                $this->expireStackCaches($stackName, $entity->id, $layerNames);
            }
            die;
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

    protected function expireStackCaches($stackName, $id, $layerNames)
    {
        /**
         * @var StacksTable $stackTable
         */
        $stackTable = TableRegistry::getTableLocator()->get($stackName);
        foreach ($layerNames as $layerName) {
            foreach ($stackTable->distillFromGivenSeed($layerName, [$id]) as $rootId) {
                $stackTable->deleteCache($rootId);
            }
        }
    }

}
