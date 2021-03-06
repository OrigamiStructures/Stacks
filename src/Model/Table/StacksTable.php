<?php
namespace Stacks\Model\Table;

use Cake\Database\Schema\TableSchemaInterface;
use Stacks\Constants\LayerCon;
use Stacks\Model\Entity\StackEntity;
use Stacks\Model\Lib\StackRegistry;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Core\ConventionsTrait;
use Stacks\Model\Lib\StackSet;
use Cake\Database\Schema\TableSchema;
use Stacks\Exception\UnknownTableException;
use Stacks\Exception\MissingMarshallerException;
use Stacks\Exception\MissingDistillerMethodException;
use Stacks\Exception\MissingStackTableRootException;
use Cake\Cache\Cache;
use Cake\Utility\Hash;
use Cake\Core\Configure;
use Stacks\Model\Lib\Layer;

/**
 * StacksTable Model
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Cake\Core\ConventionsTrait
 */
class StacksTable extends Table
{

    use ConventionsTrait;

	/**
	 * The tip-of-the-iceberg layer for this data stack
	 */
	protected $rootName = NULL;

	protected $rootTable = NULL;

	/**
     *
     * @var array
     */
    protected $layerTables = [];

    /**
     *
     * @var array
     */
    protected $stackSchema = [];

    /**
     *
     * @var array
     */
    protected $seedPoints = [];

	/**
	 * A registry object if one is used
	 *
	 * Some StackTables may store their entities in a registry so
	 * references to a single copy can be used during a Request/Response
	 * cycle. Others may not require this feature.
	 *
	 * @var mixed
	 */
	protected $registry = FALSE;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) : void {
        //Check if proper table is created
        parent::initialize($config);
		$this->configureStackCache();
		$this->validateRoot();
    }

	/**
	 * Insure the stackTable properly identifies the root in the schema
	 *
	 * A stack is `tree` data, but organinzed in layers. The `root` layer
	 * must be identified and must be a column type = layer in the schema.
	 *
	 * This value will be transfered into all the different stackEntity
	 * types that the heirarchy can create and will be an important value
	 * when working with those entities.
	 *
	 * @throws MissingStackTableRootException
	 */
	private function validateRoot() {
		if (is_null($this->rootName)) {
			throw new MissingStackTableRootException('You must set the '
					. '`rootName` property for ' . get_class($this));
		}
		if (!in_array($this->rootName, Hash::extract($this->stackSchema, '{n}.name'))){
			throw new MissingStackTableRootException('The `rootName` property in '
					. get_class($this) . ' must be listed in the stackSchema '
					. 'and be of type = layer');
		}
	}

	/**
	 * Setup the cache for this concrete stack table
	 */
	private function configureStackCache() {
		if (is_null(Cache::getConfig($this->cacheName()))) {
			Cache::setConfig($this->cacheName(),
					[
				'className' => 'File',
				'path' => CACHE . 'stack_entities' . DS,
				'prefix' => $this->cacheName() . '_',
				'duration' => '+1 week',
				'serialize' => true,
			]);
		}
	}

	/**
	 * Generate a cache key
	 *
	 * @param string $key An Rolodexwork id
	 * @return string The key
	 */
	public function cacheKey($key) {
		return $key;
	}

	/**
	 * Get the Cache config name for this concrete stack table
	 *
	 * @return string
	 */
	public function cacheName() {
		$raw = namespaceSplit(get_class($this))[1];
		return str_replace('Table', '', $raw);
	}

	public function rootName() {
		return $this->rootName;
	}

	public function rootTable() {
		return $this->{$this->rootTable};
	}

	/**
	 * Lazy load the required tables
	 *
	 * I couldn't get Associations to work in cooperation with the schema
	 * initialization that sets the custom 'layer' type properties. This is
	 * my solution to making the Tables available
	 *
	 * @param string $property
	 * @return Table|mixed
	 */
    public function __get($property) {

        if (in_array($property, $this->layerTables)) {
            $this->$property = TableRegistry::getTableLocator()->get($property);
			return $this->$property;
		}
    }

    /**
     * Override this function in order to alter the schema used by this table.
     * This function is only called after fetching the schema out of the database.
     * If you wish to provide your own schema to this table without touching the
     * database, you can override schema() or inject the definitions though that
     * method.
     *
     * ### Example:
     *
     * ```
     * protected function _initializeSchema(\Cake\Database\Schema\TableSchemaInterface $schema) {
     *  $schema->setColumnType('preferences', 'json');
     *  return $schema;
     * }
     * ```
     *
     * @param TableSchemaInterface $schema The table definition fetched from database.
     * @return TableSchemaInterface the altered schema
     */
    protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface
    {
        foreach ($this->stackSchema as $column) {
            $schema->addColumn($column['name'], $column['specs']);
        }
        return parent::_initializeSchema($schema);
    }


    /**
	 * Get the StackEntity registry if one is used
	 *
	 * @return ObjectRegistry|False
	 */
	public function registry() {
	    if(!$this->registry){
	        $this->registry = new StackRegistry();
        }
		return $this->registry;
	}

	/**
	 * The primary access point to get a concrete stack
	 *
	 * Stacks are meant to provide full context for other detail
	 * data sets that have been retrieved for some process. This allows
	 * working data queries to be small and focused. Once completed, the
	 * Stack tables back-fill the context.
	 *
	 * $options requires two indexes,
	 *		'seed' with a value matching any allowed starting point
	 *		'ids' containing an array of ids for the named seed
	 *
	 * <code>
	 * $ArtStacks->find('stacksFor',  ['seed' => 'disposition', 'ids' => $ids]);
	 * $ArtStacks->find('stacksFor',  ['seed' => 'artworks', 'ids' => $ids]);
	 * $ArtStacks->find('stacksFor',  ['seed' => 'format', 'ids' => $ids]);
	 * </code>
	 *
	 * @param Query $query
	 * @param array $options
	 * @return StackSet
	 * @throws \BadMethodCallException
	 */
	public function findStacksFor($query, $options) {

		$paginator = 'none';
        $this->validateArguments($options);
        extract($options); //$seed, $ids, $paginator
        /* @var array $seed */
        /* @var array $ids */

        if (empty($ids)) {
            return new StackSet($this->template());
        }

		$IDs = $this->distillation($seed, $ids, $paginator);
		return $this->stacksFromRoot($IDs);
    }

    /**
     * For use to return full stack sets
     *
     * @param $seed string
     * @param $ids array
     * @return StackSet
     */
    public function stacksFor($seed, $ids) {
        return $this->processStackQuery($seed, $ids);
    }

    /**
     * For use with paginator system
     *
     * @param $seed string
     * @param $ids array
     * @return callable
     */
    public function pageFor($seed, $ids) {
        return function($paginator) use ($seed, $ids) {
            return $this->processStackQuery($seed, $ids, $paginator);
        };
    }

    /**
     * @param $seed string
     * @param $ids array
     * @param bool|callable $paginator
     * @return StackSet\
     */
    private function processStackQuery($seed, $ids, $paginator = 'none') {
        if (empty($ids)) {
            return new StackSet($this->template());
        }

        $IDs = $this->distillation($seed, $ids, $paginator);
        return $this->stacksFromRoot($IDs);
    }


    /**
	 * Distill a set of seed ids down to root layer ids for the stack
	 *
	 * Discovering the root layer ids from a set of seed ids is usually
	 * pretty simple, but there are a few higher level tweaks that need
	 * to be done to the query.
	 *
	 * StackTable families have special, local, permissions filters they
	 * need to do. This alows record sharing for some data types in some
	 * managment situations.
	 *
	 * And all stacks need to respond to pagination. The root level
	 * set for the stack is the one that is paginated. So half way through
	 * the stack creation process (which is running at this point) the
	 * paginator must do its job.
	 *
	 * @param string $seed
	 * @param array $ids
     * @param string|callable
	 * @return array Root entity id set for the stack
	 */
	protected function distillation($seed, $ids, $paginator = 'none') {
		$query = $this->{$this->distillMethodName($seed)}($ids);
		$query = $this->localConditions($query);
		if ($paginator !== 'none') {
			$query = $paginator($query/*, $params, $settings*/);
		}
		$IDs = (new Layer($query->toArray(), $this->rootName()))->IDs();
		return $IDs;
	}

	/**
	 * Add any local-stack appropriate conditions to the query
	 *
	 * Override in each concrete StackTable class to suit the situation.
	 * For example PersonCardsTable adds: where(['member_type' => 'Person'])
	 * and many other places might add: where(['user_id' => $userId])
	 *
	 * I imagine a situation where superusers would need to change the
	 * 'normal' behavior, so while most uses won't carry $options,
	 * the signature allows it for fine-tuning the stack results
	 *
	 * @param Query $query
	 * @param array $options Allow special data injection just in case
	 * @return Query
	 */
	protected function localConditions($query, $options = []) {
		return $query;
	}

	/**
	 * From mixed seed types, distill to a root ID set
	 *
	 * <code>
	 * $seed = [
	 *		'identity' => [2,7],
	 *		'data_owner' => ['1234-2345-5432-999999'],
	 *		'addresses' => [12]
	 * ]
	 * </code>
	 * will return an array of the root IDs for the seeds.
	 *
	 * @param array $seeds
     * @return StackSet
	 */
	public function processSeeds($seeds) {
		$IDs = [];
		foreach ($seeds as $seed => $ids) {
			$new = $this->distillation($seed, $ids);
			$IDs = array_merge($IDs, $new);
		}
		return $this->stacksFromRoot(array_unique($IDs));
	}

	/**
	 * Get the method name for distilling a given seed into stack IDs
	 *
	 * @param string $seed
	 * @return string
	 */
	protected function distillMethodName($seed) {
		return 'distillFrom' . $this->_entityName($seed);
	}

	/**
	 * Get the method name for marshaling a given layer
	 *
	 * @param string $layer
	 * @return string
	 */
	protected function marshalMethodName($layer) {
		return 'marshal' . $this->_camelize($layer);
	}

	/**
	 * Read the stacks from registry, cache or assemble and cache them
	 *
	 * This is the destination for all the distillFor variants.
	 * It calls all the individual marshaller methods for
	 * the current concrete stack table
	 *
	 * @param array $ids Member ids
	 * @return StackSet
	 */
    public function stacksFromRoot($ids) {
		$this->stacks = $this->stackSet($this->template());
        foreach ($ids as $id) {
            if($this->stacks->element($id, LayerCon::LAYERACC_ID)){
                continue;
            }
            $stack = $this->readRegistry($id);
            if($stack === FALSE) {
                $stack = $this->readCache($id);
            }
            if($stack === FALSE) {
                $stack = $this->MarshalStack($id);
            }



			$stack = $this->readRegistry($id);
			if (!$stack && !$this->stacks->element($id, LayerCon::LAYERACC_ID)) {
				$stack = $this->writeRegistry($id, $this->MarshalStack($id));
			}

			/* Abandon any empty entities. Empty root layer = empty stack */
			if ($stack->count($this->rootName()) == 0) { continue; }

			$stack->clean();
			$this->stacks->insert($id, $stack);
			$this->writeCache($id, $stack);
		}
 		return $this->stacks;
	}

	/**
	 * Avoid marshalling a stack if we already have a copy of it
	 *
	 * A registry may be available for the stack. This will allow references
	 * to be passed out if the stack is used in more than one place during
	 * a single Request/Response cycle.
	 *
	 * A cache is used to keep the assembled stack avaialable
	 * between requests.
	 *
	 * @param string $id
     * @return StackEntity | FALSE
	 */
	protected function readRegistry($id) {
		$registry = $this->registry();
		if ($registry && $registry->has($id)) {
			return $registry->get($id);
		}
		return FALSE;
	}

	/**
	 * Store an assemble stack to aviod reassembling it later
	 *
	 * A registry may not be in use. In that case the stack is returned
	 * rather than a reference to a single, managed instance
	 *
	 * @param string $id
	 * @param StackEntity $stack or a reference to one
     * @return StackEntity
	 */
	protected function writeRegistry($id, $stack) {
		if ($this->registry()) {
			$this->registry()->load($id, $stack);
			return $this->registry()->get($id);
		}
		return $stack;
	}

	/**
	 * Read cache to see if the ID'd stack is present
	 *
     * @param string $id Stack id will generate the cache data key
     * @return mixed The cached data, or FALSE
	 */
	protected function readCache($id) {
		if (Configure::read('stackCache')) {
			return Cache::read($this->cacheKey($id), $this->cacheName());
		}
		return FALSE;
	}

	/**
	 * Write a stack to the cache
	 *
     * @param string $id
     * @param mixed $stack
     * @return bool True on successful cached, false on failure
	 */
	protected function writeCache($id, $stack) {
		if (Configure::read('stackCache')) {
			return Cache::write($this->cacheKey($id), $stack, $this->cacheName());
		} else {
			return FALSE;
		}
	}

    /**
     * Get the array of property names that contain Layer data
     * @return array|\ArrayAccess
     */
	public function layers() {
		$schema = collection($this->stackSchema);
		$layerColumns = $schema->filter(function($column, $key) {
				return $column['specs']['type'] === 'layer';
			})->toArray();
		return Hash::extract($layerColumns, '{n}.name');
	}

	/**
	 * Create, then populate a new StackEntity
	 *
	 * @param string $id
	 * @return StackEntity
	 */
	protected function MarshalStack($id) {
		$stack = $this->newEntity([])
				->setRoot($this->rootName())
				->setRootDisplaySource($this->getDisplayField());
		$stack->schema = Hash::combine($this->stackSchema, '{n}.name', '');

		foreach($this->layers() as $layer) {
			$stack = $this->{$this->marshalMethodName($layer)}($id, $stack);
			if(!is_null($stack->$layer)) {
                $stack->schema[$layer] = $stack->$layer->entityClass();
            }
		}
		return $stack;
	}

    /**
     * Make an empty StackEntity to provide introspection for StackSet if no records found
     *
     * If the found set for this StackEntity is zero, the StackSet will be
     * returned but will be completely ignorant of what it was meant
     * to be and what it was meant to contain.
     *
     * This method constructs an empty StackEntity with empty properties.
     * This structure contains information baked in from the schema that
     * will serve as a means of introspection so the StackSet will still
     * be able to operate in no-data situations.
     *
     * @return StackEntity
     */
    public function template()
    {
        $stack = $this->newEntity([])
            ->setRoot($this->rootName())
            ->setRootDisplaySource($this->getDisplayField());
        $stack->schema = Hash::combine($this->stackSchema, '{n}.name', '');

        foreach($this->layers() as $layer) {
            $stack->set([$layer => []]);
        }
        return $stack;

    }
	/**
	 * Get a new StackSet class instance based on naming conventions
	 *
	 * ArtStackTable - ArtStack (entity) - ArtStackSet
	 *
	 * @todo There is no way to override the StackSet class
	 *	    in the case of conventions-breaking usage
	 *
	 * @return StackSet
	 */
	protected function stackSet($template) {
//		$alias = $this->getAlias();
//		$className = "\App\Model\Lib\\{$alias}Set";
//		if (class_exists($className)) {
//			$result = new $className();
//		} else {
			$result = new StackSet($template);
//		}
		return $result;
	}

// <editor-fold defaultstate="collapsed" desc="finder args validation">

    /**
     * Insure the findStack arguments were correct
     *
     * @return void
     * @throws \BadMethodCallException
     */
    protected function validateArguments($options) {
        $msg = FALSE;
        if (!array_key_exists('seed', $options) || !array_key_exists('ids', $options)) {
            $msg = "Options array argument must include both 'seed' and 'ids' keys.";
            throw new \BadMethodCallException($msg);
        }

        if (!is_array($options['ids'])) {
            $msg = "The ids must be provided as an array.";
        } elseif (!in_array($options['seed'], $this->seedPoints)) {
            $msg = "{$this->getRegistryAlias()} can't do lookups starting from {$options['seed']}";
        }
        if ($msg) {
            throw new \BadMethodCallException($msg);
        }
		$options += ['paginator' => FALSE];
        return $options;
    }

// </editor-fold>

	/**
	 * Load members of a table by id
	 *
	 * The table name will be deduced from the $layer. Also, there is the
	 * assumption that a custom finder exists in that Table which is in the form
	 * Table::findTable() which can do an single or array id search.
	 * Custom finders based on IntegerQueryBehavior do the job in this system.
	 *
	 * <code>
	 * $this-_loadLayer('member', $ids);
	 *
	 * //will evaluate to
	 * $this->Members->find('members', ['values' => $ids]);
	 *
	 * //and will expect, in the Members Table the custom finder:
	 * public function findMembers($query, $options) {
	 *      //must properly handle an array of id values
	 *      //finders us
	 * }
	 * </code>
	 *
	 * @param name $layer The
	 * @param array $ids
	 * @return Query A new query on some table
	     */
    protected function _loadLayer($layer, $ids) {
		$tableName = $this->_modelNameFromKey($layer);
		$finderName = lcfirst($tableName);

		return $this->$tableName
						->find($finderName, ['values' => $ids]);
	}

	/**
	 * Throw together a temporary Join Table class and search it
	 *
	 * This will actually work for any table, but habtm tables typically
	 * don't have a named class written for them.
	 *
	 *
	 * @param string $table The name of the table class by convention
	 * @param string $column Name of the integer column to search
	 * @param array $ids
	     */
	protected function _distillFromJoinTable($table, $column, $ids) {
		$joinTable = TableRegistry::getTableLocator()
				->get($table)
				->addBehavior('IntegerQuery');

		$q = $joinTable->find('all');
		$q = $joinTable->integer($q, $column, $ids);
		return $q;
	}

	public function hasSeed($name) {
		return in_array($name, $this->seedPoints);
	}

    /**
     * Add layer tables
     *
     * Check to be sure that the added tables are all valid tables
     *
     * @throws UnknownTableException
     * @param array $addedTables
     */
	protected function addLayerTable(array $addedTables)
    {
        foreach ($addedTables as $index => $addedTable) {
            if(is_a(
					TableRegistry::getTableLocator()->get($addedTable),
					'App\Model\Table\AppTable')
               || is_a(
                    TableRegistry::getTableLocator()->get($addedTable),
                    'Cake\ORM\Table')){
                $this->layerTables[] = $addedTable;
            } else {
                throw new UnknownTableException("StacksTable initialization discovered
                $addedTable is not a valid table name");
            }
        }
        $this->layerTables = array_unique($this->layerTables);
	}

	/**
	 *
	 * @param array $addedSchemaNames
	 * @throws MissingMarshallerException
	 */
    protected function addStackSchema(array $addedSchemaNames)
    {
        foreach ($addedSchemaNames as $schemaName) {
            $methodName = $this->marshalMethodName($schemaName);
            if(method_exists($this, $methodName)){
                $this->stackSchema[] = [
                    'name' => $schemaName,
                    'specs' => ['type' => 'layer']
                    ];
            } else {
                throw new MissingMarshallerException("StacksTable initialization discovered
                there is not a proper $methodName function");
            }
        }
    }

    protected function addSeedPoint(array $seedPoints)
    {
        foreach ($seedPoints as $index => $seedPoint) {
            $methodName = $this->distillMethodName($seedPoint);
            if(method_exists($this, $methodName)){
                if(!in_array($seedPoint, $this->seedPoints)){
                    $this->seedPoints[] = $seedPoint;
                }
            } else {
                throw new MissingDistillerMethodException("StacksTable initialization discovered
                there is not a proper $methodName function");
            }
        }
    }
}
