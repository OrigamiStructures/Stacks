<?php

namespace Stacks\Listeners;

use App\Lib\Introspection;
use App\Model\Entity\UserIdentity;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Filesystem\Folder;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Stacks\Constants\CacheCon;
use Stacks\Model\Table\StacksTable;
use function collection;
use Cake\Utility\Inflector;

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
class LayerSave implements EventListenerInterface
{

    /**
     * @var array
     */
    public $logResult = [];


    /**
     * Sidebar: Cake Events have requirements; register these event listeners.
     *
     * Application.php will register this listener so it'll
     * be on all the time.
     *
     * Model.afterSaveCommit is the workhorse that is integrated into the Cake ORM.
     * It will run on every top-level entity save. Meaning any individual associated
     * entities will be ignored because we only consider the parent significant
     * That is among the limitations and assumptions of this listener.
     *
     * Another limitation is that there is no way to know what alias an entity (or its
     * controlling table) is going by when the save happens. This makes the identity
     * of the table that triggers this event hazy.
     *
     * Combine this with the reality that the layer data stored in stacks also lives
     * under an alias and only our developer-defined table-value in the map
     * identifies its origin and a picture of uncertainty emerges.
     *
     * These are the two sides of the equation that we're trying to match up via
     * our map. Neither has an immutable identity in our code. So our goal is
     * to always have the base table name be used. We design the map fragments in
     * each concrete stack table this way, and we design our forms to work this way.
     *
     * We 'simply' have to coordinate these facts to guarantee the needed match is always
     * made and the caches always expire. But the stakes are high. A missed match is
     * a big deal!
     * @todo Can we have 'never matched' events alert the developer by log or email?
     *    I have at least put more text in the never-matched to ask if the Root Table
     *    was used (as this turned out to be an obscure bug)
     * @todo It might be possible to read the Model\Table classes and build an inheritance
     *    map for them
     *
     * We have a backup plan though. Again, it depends on developer attentiveness.
     * The Stacks.directCacheExpiry action can be called by any code. That will allow
     * logic that back-fills cache expiration in cases where some associated data needs
     * examination.
     *
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return [
//            'Model.afterSaveCommit' => 'afterSaveCommit',
            'Model.beforeDelete' => 'afterSaveCommit',
            'Stack.directCacheExpiry' => 'directCacheExpiry',
            'Model.afterSave' => 'afterSaveCommit',
        ];
    }

    /**
     * Another sidebar: The process to make the lookup map
     *
     * When no mapping of table classes to their layer manifestation in the stacks
     * exists, we have to go on a scavenger hunt to collect all the required information.
     *
     * We make the assumption that all concrete stack table classes will be stored in the
     * application's Model\Table namespace and that they will be named according to our
     * convention 'concreteName'StackTable.php
     *
     * Based on these assumptions we derive all the class names from the directory contents,
     * instantiate each class in turn and have them run a method that adds their
     * layer-to-table map to a collection point; a cache file stored in the app's
     * cache/persitent directory (1 year expiration).
     *
     * WARNING OH WISE DEVELOPER
     * If any stack table schemas are changed, or new ones created, you'de better run over
     * to that cache directory and kill the old cache file or cache expiration will become
     * unreliable and unpredictable bad data will result!!
     *
     * @return array
     */
    protected function compileLayerMap()
    {
        $stackTableList = Introspection::stackTables();
        Cache::write(
            CacheCon::SCKEY,
            ['Map Created' => 'Map Created: ' . Time::now()->toDateTimeString()],
            CacheCon::SCCONFIG
        );
        collection($stackTableList)
            ->map(function ($filename){
                $alias = str_replace('Table.php', '', $filename);
                $table = TableRegistry::getTableLocator()->get($alias);
                /* @var StacksTable $table */
                $table->compileLayerMapFragment();
                TableRegistry::getTableLocator()->remove($alias);
            })->toArray();
        return Cache::read(CacheCon::SCKEY, CacheCon::SCCONFIG);
    }

    /**
     * And here is our ear-to-the-ground
     *
     * There are a few save activities that don't have anything to do with
     * Stack Table, so we need a guard to filter out the noise.
     *
     * Then, we read the map-of-legend from long-term storage. And, as previously mentioned
     * if the cache isn't available... pay no attention to the man behind the curtain.
     *
     * At this point, we have the entity that changed (where we can read the id of the record),
     * the name of the table responsible for the entity, and the map with table names as the
     * first level key. It's a simple matter then to get the sub-map describing the role
     * of this table in the currently configured stacks.
     *
     * And that's exactly what we do; when we pull the map node named by the acting table we'll
     * have a new array with the names of potentially impacted stack tables. We walk through
     * these in a loop, each step will list all the layers in a given stack table which
     * this acting table populates.
     *
     * This new data, stackTable name, modified entity id, and list of layers containing
     * this class of data, is all sent out for the final task, to discover what specific
     * stacks this data expire their caches.
     *
     * @param $event Event
     * @param $entity Entity
     * @param $options
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterSaveCommit($event, $entity = null, $options = null)
    {
        if (! in_array($event->getSubject()->getAlias(), ['Panels', 'Requests', 'Preferences'])) {
//            osd($event->getSubject()->getAlias());
//            osd($this->getParticipationMap());
//            osd(Inflector::underscore($entity->getSource()));
            $map = $this->getParticipationMap();
            $table = $entity->getSource();
            $this->logResult = [];
            foreach (Hash::get($map, Inflector::underscore($table)) ?? [] as $stackName => $layerNames) {
//                osd($stackName, 'stack name');
//                osd($layerNames, 'layer name');
                $this->expireStackCaches($stackName, $entity->id, $layerNames);
            }
            $this->writeResultLog($table, $entity->id);
        }
    }

    /**
     * Jack-the-cache-killer: where the true magic of StackTable distillers is revealed!
     *
     * Stack tables are designed with one distiller for each layer type they contain.
     * The distillers accept an array of layer record ids and will return the ids of all
     * the stack records of the a concrete type that would contain any of the seeds.
     *
     * Normally, these distillers are used to start the process of building requested
     * stacks. But in this process, we capture the set of root ids and use them as
     * keys to locate and delete cached copies of the stacks so they can be remade with
     * our newly modified data next time the stack is requested.
     *
     * This was the point of the whole exercise! Mission accomplished.k
     *
     * Since both the stack cache configuration and the tools to access it are dynamic
     * features of the stack tables, we need to instantiate each table and delegate
     * work to them.
     *
     * There may be a lot of tables, so we take a little care with the cache registry
     * to insure that any objects that get created just for this use get cleaned up afterwards.
     *
     * @param $stackName string
     * @param $id int|string
     * @param $layerNames array
     */
    protected function expireStackCaches($stackName, $id, $layerNames)
    {
        /**
         * @var StacksTable $stackTable
         */
        $preExisting = TableRegistry::getTableLocator()->exists($stackName);
        $stackTable = TableRegistry::getTableLocator()->get($stackName);

        foreach ($layerNames as $layerName) {
            foreach ($stackTable->distillFromGivenSeed($layerName, [$id])->toArray() as $entity) {
                $result = $stackTable->deleteCache($entity->id);
                $this->logResult[] = $result . "{$stackName}[{$entity->id}] expired on change to {$layerName}[{$id}]";
            }
        }

        if (!$preExisting) {
            TableRegistry::getTableLocator()->remove($stackName);
        }
    }

    /**
     * Expire stack caches that include or should include this record
     *
     * This event relies on direct values rather than tricky deduction and
     * is a fallback against the limitations of the main afterSaveCommit event.
     * It allows for manual triggering when needed.
     *
     * Simple create an array that carries the id of a potential stack-participant
     * and the name of the table class the record belongs to and send it
     * on your event like this:
     *
     *    $data = ['id' => $id, 'table' = $alias]
     *
     *    $event = new Event('Stack.directCacheExpiry', $this, $data);
     *    $this->getEventManager()->dispatch($event);
     *
     * @param $event Event
     * @param $object object the object from which this event was triggered
     * @param $data array ['id' => $id, 'table' = $alias]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function directCacheExpiry($event, $object, $data) {
        $data = $event->getData();
        $map = $this->getParticipationMap();
        $this->logResult = [];
        foreach (Hash::get($map, strtolower($data['table'])) ?? [] as $stackName => $layerNames) {
            $this->expireStackCaches($stackName, $data['id'], $layerNames);
        }
        $this->writeResultLog($data['table'], $data['id']);
    }

    protected function writeResultLog($triggerTable, $triggerId)
    {
        // don't log while testing
        if (Configure::read('test-mode')) { return; }

        $request = Router::getRequest();
        $identity = $request->getAttribute('identity');
        /* @var UserIdentity $identity */
        $name = $identity->getPerson('first_name') . ' ' . $identity->getPerson('last_name');
        $trigger = "{$triggerTable}[{$triggerId}]";
        $controller = $request->getParam('controller');
        $action = $request->getParam('action');
        $header = "$name modified $trigger." . PHP_EOL . "Cache management was triggered through $controller/$action";

        if (empty($this->logResult)) {
            $this->logResult = ["No mappings found. Is $triggerTable a root table?"];
        }
        array_unshift($this->logResult, $header);
        Log::write(
            'info',
            implode(PHP_EOL, $this->logResult) . PHP_EOL,
            'stack_cache_expiry');
    }

    public function getParticipationMap()
    {
        return Cache::read(CacheCon::SCKEY, CacheCon::SCCONFIG) ?? $this->compileLayerMap();
    }

    public function deleteParticipationMap()
    {
        return Cache::delete(CacheCon::SCKEY, CacheCon::SCCONFIG);
    }

}
