<?php
namespace Stacks\Model\Lib;

use Cake\Datasource\ResultSetInterface;
use http\Exception\BadMethodCallException;
use Stacks\Interfaces\LayerStructureInterface;
use Stacks\Model\Entity\StackEntity;
use Stacks\Model\Traits\LayerElementAccessTrait;
use Cake\Core\ConventionsTrait;
use Cake\Utility\Text;

/**
 * StackSet
 *
 * This is a collector class which holds sets of Entities that extend StackEntity
 *
 * This class provides access to the stored entities and their data
 * to make it easier to pull out stacks, layers, and merged collections of
 * entities from multiple stack.
 *
 * @author dondrake
 */
class StackSet implements LayerStructureInterface, ResultSetInterface {

	use LayerElementAccessTrait;
	use ConventionsTrait;
	use ResultSetSatisfactionTrait;

	protected $_data = [];

    /**
     * A fully constructed but empty StackEntity concrete type
     *
     * This allows the stackSet to do introspection on entity
     * even if it contains no found records. This will allow
     * the class to act normally in all code whether it has
     * content or not.
     *
     * In particular, this was added so getLayer() in 'empty'
     * situations could function
     *
     * @var StackEntity
     */
	protected $template;

	protected $_stackName;

	protected $paginatedTable;

	public function __construct($stackEntityTemplate)
    {
        $this->template = $stackEntityTemplate;
    }

    public function getTemplate()
    {
        return $this->template;
    }
    //<editor-fold desc="LayerStructureInterface Realization">
    /**
     * Gather the available data at this level and package the iterator
     *
     * @param $name string
     * @return LayerAccessProcessor
     */
    public function getLayer($name, $entityClass = null)
    {
        if (is_null($this->template->$name)) {
            $msg = "The layer '$name' is not the name of a layer in the "
                . get_class($this->template) . " instances stored in " . get_class($this);
            throw new \BadMethodCallException($msg);
        }

        $entityClass = $entityClass ?? $this->template->$name->entityClass();
        $stacks = $this->getData();
        $Product = new LayerAccessProcessor($name, $entityClass);
        foreach ($stacks as $stack) {
            if (is_a($stack->$name, '\Stacks\Model\Lib\Layer')) {
                $result = $stack->$name;
            } else {
                $result = [];
            }
            $Product->insert($result);
        }
        return $Product;
    }

    /**
     * Get the list of layer in these stack entities
     *
     * @return array|string[]
     */
    public function getLayerList()
    {
        return $this->template->getVisible();
    }

    /**
     * Get an new LayerAccessArgs instance
     * @return LayerAccessArgs
     */
    public function getArgObj()
    {
        return new LayerAccessArgs();
    }
    //</editor-fold>

    //<editor-fold desc="LayerElementAccessTrait abstract completion">
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return mixed
     */
    public function getRootLayerName()
    {
        return $this->_stackName;
    }

    /**
     * @return mixed
     */
    public function getPaginatedTableName()
    {
        return $this->paginatedTable;
    }

    /**
     * Get all the ids accross all the stored StackEntities or the Layer entities
     *
     * This is a collection-level method that matches the StackEntity's and Layer's
     * IDs() methods. These form a pass-through chain.
     *
     * Calling IDs() from this level will insure unique results if
     * Layer IDs are pulled.
     *
     * StackEntity IDs will be from the primary entity propery and will
     * be unique becuase the set structure insures it.
     *
     * @param string $layer
     * @return array
     */
    public function IDs($layer = null) {
        if(is_null($layer)){
            return array_keys($this->getData());
        }
        return $this->getLayer($layer)
            ->toDistinctList('id');
    }

    //</editor-fold>

    //<editor-fold desc="Public Associated Data Features">

    /**
     * Create a LAA that targets members of a layer linked to one point
     *
     * The return LAA can be further sorted or paginated
     *
     * @param string $foreign the foreign key name
     * @param int|string $foreign_id the id
     * @param null|string $linked the layer name if we're not directly on a Layer object
     * @return LayerAccessArgs
     */
    public function linkedTo($foreign, $foreign_id, $linked = null) {
        $accessProcessor = $this->getLayer($linked);
        $foreign_key = $this->_modelKey($foreign);
        return $accessProcessor
            ->find()
            ->specifyFilter($foreign_key, $foreign_id);
    }

    /**
	 * Return all StackEntities that contain a layer entity with id = $id
	 *
	 * @param string $layer
	 * @param string $id
	 * @return array
	 */
	public function ownerOf($layer, $id) {
		return collection($this->_data)
		    ->reduce(function($accum, $stack) use ($layer, $id) {
                if ($stack->exists($layer, $id)) { $accum[] = $stack; }
		        return $accum;
		    }, []);
	}

    /**
     * Get all StackEntities containing any of the layer elements in the set
     *
     * @param $layer string The layer to search in
     * @param $ids array The ids to search for
     */
    public function stacksContaining($layer, $ids)
    {
        $stacks = [];
        foreach ($this->getData() as $stack) {
            //get the ids of the layer members in this stackentity
            //and intersect with the found set
            $intersection = array_intersect($stack->$layer->IDs(), $ids);
            if (count($intersection) > 0) {
                //if there was some overlap, save this stack for return.
                $stacks[$stack->rootID()] = $stack;
            }
        }
        return $stacks;
    }

    /**
     * Make a new StackSet from the provide array of StackEntities
     *
     * @param StackEntity[] $data
     * @return StackSet
     */
    public function newStackSet($data)
    {
        if (!is_array($data) || !$this->isHomogenous($data)) {
            $msg = 'Stacks can only be made from arrays of \'same-type\' statck entities';
            throw new \BadMethodCallException($msg);
        }
        $set = new StackSet($this->template);
        collection($data)
            ->map(function($stack) use ($set) {
                $set->insertToStackSet($stack->rootId(), $stack);
            })
            ->toArray();
        return $set;
    }

    /**
     * array contain only same stack entity types as this stack set?
     *
     * @param StackEntity[] $data
     * @return bool
     */
    private function isHomogenous($data)
    {
        $class_name = get_class($this->template);
        return collection($data)
            ->reduce(function($accum, $stackEntity, $index) use ($class_name) {
                return $accum && (get_class($stackEntity) === $class_name);
            }, true);
    }

    //</editor-fold>

    /**
     * Add another entity to the StackSet
     *
     * @param string $id
     * @param StackEntity $stack
     */
    public function insertToStackSet($id, $stack) {
        $this->_data[$id] = $stack;
        if (!isset($this->_stackName)) {
            $this->_stackName = $stack->getRootLayerName();
            $this->paginatedModel = $this->_modelNameFromKey($this->_stackName);
        }
    }
    public function __debugInfo()
    {
        return [
            '[_data]' => 'Contains ' . count($this->_data) . ' elements, '
                . Text::toList($this->IDs()),
            '[$stackName]' => $this->_stackName
        ];
    }

    /**
     * Returns whether or not there are elements in this collection
     *
     * ### Example:
     *
     * ```
     * $items [1, 2, 3];
     * (new Collection($items))->isEmpty(); // false
     * ```
     *
     * ```
     * (new Collection([]))->isEmpty(); // true
     * ```
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->getData());
    }

}
