<?php

namespace App\Model\Lib;

use App\Exception\MissingPropertyException;
use App\Interfaces\LayerAccessInterface;
use App\Interfaces\LayerTaskInterface;
use App\Interfaces\xxxLayerAccessInterface;
use BadMethodCallException;
use Cake\Utility\Inflector;
use App\Model\Lib\ValueSource;
use App\Lib\Traits\ErrorRegistryTrait;
use App\Model\Lib\ValueSourceRegistry;
use http\Exception\InvalidArgumentException;

/**
 * LayerAccessArgs manages the arguments used by Set/Stack/Layer::load()
 *
 * load(), and the several methods that support and extend it make use of
 * many parameter. This class encapsulates and manages them.
 *
 * Targeting downstream nodes
 * ------------------------------------------
 * layer : The classes upstream from Layers will often need to name the
 *        layer that will be operated on.
 * id_index : To support record linking, `layer` content and `StackSet`
 *        content are indexed by their ID (or primary entity ID)
 *
 * Pagination
 * All results will be paginated using these values if set
 * ------------------------------------------
 * page : which page of found data to return
 * limit : how many elements per page
 *
 * properties found in PaginationComponent
 * limit, maxLimit, sortWhiteList, finder, sort, direction, page, order
 *
 * Data filtering
 * ------------------------------------------
 * TRUE allows the entity into the set, FALSE excludes it
 * Easiest to build these using the specifyFilter() method
 * value_source : The source of the datum to test (property or method)
 *        methods must not require arguments
 * filter_value : The value to compare
 * filter_operator : The kind of comparison to make
 *
 * Return data structure
 * ------------------------------------------
 * Most processes return an array containing entities. The values() and
 * keyedList() methods will reduce the result to an array of values or
 * an indexed array respectively
 *
 * @author dondrake
 */
class LayerAccessArgs implements LayerAccessInterface
{

    use ErrorRegistryTrait;

    /**
     * @var LayerTaskInterface
     */
    protected $data;

    protected $_registry;

    protected $change_timestamp;

// <editor-fold defaultstate="collapsed" desc="PAGINATION PROPERTIES">

    /**
     * Page to return for paginated results
     *
     * @var int
     */
    private $_page = FALSE;

    /**
     * Number of entities per page
     *
     * 0 = not paginated
     * -1 = explicit 'all' request
     * 1 = first
     * x = number of entities per page
     *
     * @var int
     */
    private $_limit = FALSE;

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="LAYER PROPERTY">

    /**
     * Name of this layer property
     *
     * @var string
     */
    private $_layer = FALSE;

    // </editor-fold>

// <editor-fold defaultstate="collapsed" desc="VALUE-SOURCE PROPERTIES">

	private $source_node = [
		'value' => FALSE,
		'key' => FALSE,
		'filter' => FALSE,
        'resultValue' => FALSE,
        'resultKey' => FALSE,
        'distinctValue' => FALSE
	];

    // </editor-fold>

// <editor-fold defaultstate="collapsed" desc="FILTER PROPERTIES">

    private $_filter_value = FALSE;
    private $_filter_value_isset = FALSE;
    private $_filter_operator = FALSE;

// </editor-fold>

//<editor-fold desc="SORT VALUES">
    private $_sortDir = FALSE;
    private $_sortType = FALSE;
    private $_sortColumn = FALSE;

//</editor-fold>

//<editor-fold desc="NEW METHODS FOR REFACTOR">

    public function specifySort($value_source, $direction, $type = SORT_NATURAL)
    {
        $this->setSortColumn($value_source);
        $this->setSortDir($direction);
        $this->setSortType($type);
        return $this;
    }

    public function setSortDir($direction)
    {
        if(!in_array($direction, [SORT_DESC, SORT_ASC])) {
            $msg = 'The sort diection must be one of the php SORT_x constants (or values 3 or 4).';
            throw new InvalidArgumentException($msg);
        }
        $this->_sortDir = $direction;
        return $this;
    }

    /**
     * Set the sort type
     *
     * @throws \InvalidArgumentException
     * @param $type int
     * @return $this
     */
    public function setSortType($type)
    {
        if(!in_array($type, [SORT_NUMERIC, SORT_STRING, SORT_NATURAL, SORT_LOCALE_STRING])) {
            $msg = 'The sort type must be one of the php SORT_x constants (or values 1, 2, 6, or 5).';
            throw new InvalidArgumentException($msg);
        }
        $this->_sortType = $type;
        return $this;
    }

    /**
     * @todo what is the story with _sort_value_isset? Can't verify ValObj any other what? deleted for now
     *
     * @todo supressed ValueObjects. Can they be used?
     * @param $valueSource
     * @return $this
     */
    public function setSortColumn($valueSource)
    {
        $this->_sortColumn = $valueSource;
        return $this;
    }

    /**
     * null the stored data so these settings can be reused with new data
     */
    public function resetData()
    {
        $this->data = null;
    }

    /**
     * Have the minimum required filter values been specified?
     *
     * If so, a filter process be run on the data
     *
     * @return bool
     */
    public function hasFilter()
    {
        return $this->source_node['filter'] && $this->valueOf('filter_value_isset');
    }

    /**
     * Have the minimum required sort values be specified?
     *
     * If so, a sort process be run on the data
     *
     * @return bool
     */
    public function hasSort()
    {
        return $this->_sortDir !== FALSE && $this->_sortType !== FALSE && $this->_sortColumn !== FALSE;
    }

    /**
     * Have the minimum required pagination values been specified?
     *
     * If so, a pagination process be run on the data
     *
     * @return bool
     */
    public function hasPagination()
    {
        return $this->_page !== FALSE && $this->_limit !== FALSE;
    }

    /**
     * Get the result as an array of entities
     *
     * @return array
     * @throws MissingPropertyException
     */
    public function toArray()
    {
        $this->_validateExecution();
        return $this->data->toArray();

    }

    /**
     * Get the result as Layer object
     *
     * @return Layer
     * @throws MissingPropertyException
     */
    public function toLayer()
    {
        $this->_validateExecution();
        return $this->data->toLayer();
    }

    /**
     * Get an array of values
     *
     * @param $valueSource string|ValueSource
     * @return array
     * @throws MissingPropertyException
     */
    public function toValueList($valueSource = null)
    {
        $this->_validateExecution();
        return $this->data->toValueList($valueSource);
    }

    /**
     * Get a key => value list
     *
     * @param $keySource string|ValueSource
     * @param $valueSource string|ValueSource
     * @return array
     * @throws MissingPropertyException
     */
    public function toKeyValueList($keySource = null, $valueSource = null)
    {
        $this->_validateExecution();
        return $this->data->toKeyValueList($keySource, $valueSource);
    }

    /**
     * Get a list of distinct values
     *
     * @param $valueSource string|ValueSource
     * @return array
     * @throws MissingPropertyException
     */
    public function toDistinctList($valueSource = null)
    {
        $this->_validateExecution();
        return $this->data->toDistinctList($valueSource);
    }

    /**
     * Get the stored registry instance
     *
     * @return ValueSourceRegistry
     */
    public function getValueRegistry()
    {
        return $this->_registry;
    }

    /**
     * Insure data is present and flag $this as process initiator
     *
     * @return void
     * @throws MissingPropertyException
     */
    protected function _validateExecution()
    {
        if (!isset($this->data)) {
            $msg = 'Processing was requested on a bare LayerAccessArgs object.';
            throw new MissingPropertyException($msg);
        }
    }

//</editor-fold>

    public function __construct($data = FALSE)
    {
        $this->_registry = new ValueSourceRegistry();
        if ($data) {
            $this->data = $data;
        }
        return $this->checkOut();
    }

    public function checkOut()
    {
        $this->change_timestamp = microtime();
        return $this;
    }
    public function getTimestamp()
    {
        return $this->change_timestamp;
    }

    public function data()
    {
        return $this->data;
    }

// <editor-fold defaultstate="collapsed" desc="LAYER ARGUMENT">

    public function setLayer($param)
    {
        if ($this->hasLayer() && $this->valueOf('layer') != $param) {
            $this->registerError('Can\'t change `layer` after it\'s been set.');
        } else {
            $this->_layer = $param;
            $this->setupValueObjects('layer');
        }
        return $this->checkOut();
    }

    public function accessNodeObject($name)
    {
        return $this->getValueRegistry()->get($name);
    }

    public function setAccessNodeObject($objectName, $nodeName)
    {
        if (
            $this->hasAccessNodeName($objectName)
            && $this->source_node[$objectName] != $nodeName) {
            $this->registerError("Can't change `{$objectName}` object's "
                . "source node name after it's been set.");
        } else {
            $this->source_node[$objectName] = $nodeName;
            $this->setupValueObjects($objectName);
        }
        return $this->checkOut();

    }

    /**
     * Make a ValueSource object or defer the task for later
     *
     * 'layer'
     *        if the Value and Key objects haven't been made yet but
     *        the source node is know for either, we can now make
     *        that object since the layer is now known
     * 'value'
     *        set the layer if we can
     *        if the ValueObject isn't yet constructued but the layer is
     *        known, make the object since the source node is now known
     * 'key'
     *        set the layer if we can
     *        if the KeyObject isn't yet constructed but the layer is
     *        known, make the object since the key node is now known
     *
     * @param type $origin
     */
    private function setupValueObjects($origin)
    {
        switch ($origin) {
            case 'layer':
                $this->registerSourceNodes();
                break;
            default:
                $this->evaluateLayer();
                if (!$this->hasAccessNodeObject($origin) && $this->hasLayer()) {
                    $this->buildAccessObject($origin);
                }
                break;
        }
    }

    private function registerSourceNodes()
    {
        foreach (array_keys($this->source_node) as $name) {
            if (!$this->hasAccessNodeObject($name) && $this->hasAccessNodeName($name)) {
                $this->buildAccessObject($name);
            }
        }
    }

    private function evaluateLayer()
    {
        if (!$this->hasLayer() && is_a($this->data(), 'App\Model\Lib\Layer')) {
            $this->setLayer($this->data()->layerName());
        }
    }

    private function buildAccessObject($name)
    {
        $result = $this->getValueRegistry()->load(
            $name,
            [
                'entity' => $this->valueOf('layer'),
                'node' => $this->source_node[$name]
            ]
        );
    }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="VALIDATION CALLS -- hasXX(), isXX()">

    public function hasLayer()
    {
        return $this->_layer !== FALSE;
    }


    public function hasAccessNodeName($name)
    {
        return $this->source_node[$name] !== FALSE;
    }

    public function hasAccessNodeObject($name)
    {
        return !is_null($this->getValueRegistry()->get($name));
    }

    /**
     * Are the minimum required arguments set to allow filter operations?
     *
     * Requires 'valueSource and that a 'filterValue' has been set.
     *
     * @return boolean
     */
    public function isFilter()
    {
        return $this->source_node['filter'] && $this->valueOf('filter_value_isset');
    }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="PAGINATION ARGUMENTS">

    /**
     * Set a page to get and how many units are on the page
     *
     * @param int $page
     * @param int $limit
     */
    public function specifyPagination($page, $limit)
    {
        $this->setPage($page);
        $this->setLimit($limit);
        return $this->checkOut();
    }

    public function setPage($param)
    {
        $this->_page = $param;
        return $this->checkOut();
    }

    /**
     * Set the number of elements per page
     *
     * -1 will return all
     * 1 is actually 'first in page' rather than 'first in collection'
     *
     * @param type $param
     * @return \App\Model\Lib\LayerAccessArgs
     */
    public function setLimit($param)
    {
        $param = $param === 'all' ? -1 : $param;
        $param = $param === 'first' ? 1 : $param;
        $this->_limit = $param;
        return $this->checkOut();
    }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="ID-INDEX ARGUMENT">

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="VALUE RETRIEVAL -- PROPOSED --">

    private function getEntityValue($pointer, $entity)
    {
        if (in_array($pointer, $entity->visibleProperties())) {
            return $entity->$pointer;
        } elseif (method_exists($entity, $pointer)) {
            return $entity->$pointer();
        } else {
            return null;
        }
    }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="FILTER ARGUMENTS">

    /**
     * Set up the filter params all at once
     *
     * @param string $value_source A property_name or method_name()
     * @param mixed $filter_value The value to compare to the $source_value
     * @param string $filter_operator The kind of comparison to make
     */
    public function specifyFilter($value_source, $filter_value, $filter_operator = FALSE)
    {
        $this->setFilterOperator($filter_operator);
        $this->setAccessNodeObject('filter', $value_source);
        $this->setFilterValue($filter_value);
        return $this->checkOut();
    }

    /**
     * Set the property or method that will be filtered
     *
     * @param $value_source string
     */
    public function setFilterTestSubject($value_source)
    {
        $this->setAccessNodeObject('filter', $value_source);
        return $this->checkOut();
    }

    /**
     * Set a filterValue and flag that this has been done
     *
     * `filterValue` compares to the value of `valueSource` using `filterOperator`
     *
     * Filtering may be done on any value, including FALSE.
     * So there is no safe direct test to see if a value has been stored.
     * Instead, filter-value-isset is marked as our indicator.
     *
     * filter_operator will be assumed as == if it hasn't been set
     *
     * @param mixed $param
     * @return \App\Model\Lib\LayerAccessArgs
     */
    public function setFilterValue($param)
    {
        $this->_filter_value_isset = TRUE;
        $this->_filter_value = $param;
        if (!$this->valueOf('filterOperator')) {
            if (is_array($param)) {
                $default_operator = 'in_array';
            } else {
                $default_operator = '==';
            }
            $this->setFilterOperator($default_operator);
        }
        return $this->checkOut();
    }

    /**
     * Set a comparison operation for filtering sourceValues
     *
     * [==, in_array] - defaults based on filterValue type
     *
     * Other options
     * !=, ===, !==, <, >, <=, >=,
     * Options that won't use filterValue
     * true (=== T), false (=== F), truthy (boolean of value)
     *
     * @param string $param
     * @return \App\Model\Lib\LayerAccessArgs
     */
    public function setFilterOperator($param)
    {
        $this->_filter_operator = $param;
        return $this->checkOut();
    }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="UNIVERSAL GETTER">

    /**
     * One call returns them all
     *
     * Properties can be identified
     *        under_scored
     *        _under_scored
     *        underScored
     *        UnderScored
     *
     * @param string $param Name of the property to return
     *
     * @return mixed
     * @throws BadMethodCallException
     */
    public function valueOf($param)
    {
        // when some_name style is submitted
        $property = '_' . trim($param, '_');
        if (isset($this->$property)) {
            return $this->$property;
        }
        // when someName style is submitted
        $property = '_' . Inflector::underscore($param);
        if (isset($this->$property)) {
            return $this->$property;
        }
        return '';
        if (!isset($this->$property)) {
            throw new BadMethodCallException("Request to get LayerAccessParams::$param. The property does not exist.");
        }
        return $this->$property;
    }

// </editor-fold>

    /**
     * Choose a comparison function based on a provided operator
     *
     * An unknown operator will yield a function that never finds matches
     *
     * @param string $operator
     * @return callable
     */
    public function selectComparison($operator)
    {
        $ops = [
            'bad_op' => function ($actual, $test_value) {
                return FALSE;
            },
            '==' => function ($actual, $test_value) {
                return $actual == $test_value;
            },
            '!=' => function ($actual, $test_value) {
                return $actual != $test_value;
            },
            '===' => function ($actual, $test_value) {
                return $actual === $test_value;
            },
            '!==' => function ($actual, $test_value) {
                return $actual !== $test_value;
            },
            '<' => function ($actual, $test_value) {
                return $actual < $test_value;
            },
            '>' => function ($actual, $test_value) {
                return $actual > $test_value;
            },
            '<=' => function ($actual, $test_value) {
                return $actual <= $test_value;
            },
            '>=' => function ($actual, $test_value) {
                return $actual >= $test_value;
            },
            'true' => function ($actual, $test_value) {
                return $actual === TRUE;
            },
            'false' => function ($actual, $test_value) {
                return $actual === FALSE;
            },
            'in_array' => function ($actual, $test_values) {
                return in_array($actual, $test_values);
            },
            '!in_array' => function ($actual, $test_values) {
                return !in_array($actual, $test_values);
            },
            'truthy' => function ($actual, $test_value) {
                return (boolean)$actual;
            }
        ];

        if (!array_key_exists($operator, $ops)) {
            return $ops['bad_op'];
        } else {
            return $ops[$operator];
        }

    }

    public function __debugInfo()
    {
        $val = [
            'change_timestamp',
            '_page', '_limit',
            '_layer', 'source_node', '_filter_value',
            '_filter_value_isset', '_filter_operator',
            '_sortDir', '_sortType', '_sortColumn',
        ];
        $result = [
            '[data]' => isset($this->data)
                ? get_class($this->data) . ' containing ' . $this->data->rawCount() . 'items.'
                : 'not set',
            '[_registry]' => $this->_registry,
        ];
        foreach ($val as $property) {
            $result[$property] = $this->$property;
        }
        return $result;
    }
}
