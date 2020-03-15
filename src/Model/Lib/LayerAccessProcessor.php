<?php


namespace App\Model\Lib;

use App\Interfaces\Layer;
use App\Interfaces\LayerAccessInterface;
use App\Interfaces\LayerStructureInterface;
use App\Interfaces\LayerTaskInterface;
use Cake\Collection\Collection;

/**
 * LayerAccessProcessor
 *
 * @package App\Model\Lib
 */
class LayerAccessProcessor implements LayerAccessInterface, LayerTaskInterface
{

    protected $layerName;
    protected $entityClass;
    /**
     * @var LayerAccessArgs
     */
    protected $AccessArgs = null;

    /**
     * microtime stamp of the AccessArg object responsible for the current ResultIterator
     *
     * @var null|microtime
     */
    protected $previousArgsTimestamp = null;

    /**
     * All the entities to operate on
     *
     * @var \AppendIterator
     */
    protected $AppendIterator;

    /**
     * The product of processing AppendIterator data using AccessArgs
     *
     * After processing, this property will store an ArrayIterator.
     * Prior to processing, this property will store FALSE. Changes to
     * AccessArgs will set it to FALSE also.
     *
     * @todo how do we detect internal chages to AccessArgs?
     *
     * @var array|\ArrayIterator
     */
    protected $ResultIterator = FALSE;


    public function __construct($layerName, $entityClass)
    {
        $this->AppendIterator = new LayerAppendIterator();
        $this->layerName = $layerName;
        $this->entityClass = $entityClass;
    }

    /**
     * Get the currently stored AppendIterator
     *
     * @return LayerAppendIterator|\AppendIterator
     */
    public function getAppendIterator() {
        return $this->AppendIterator;
    }

    /**
     * microtime stamp of the AccessArg object responsible for the current ResultIterator
     * @return microtime|null
     */
    public function getPreviousArgsTimestamp()
    {
        return $this->previousArgsTimestamp;
    }

    /**
     * Make the class tollerant of the input
     *
     * This will take any form of entity data we might have from our system;
     *  - array of entities
     *  - a Layer object
     *  - a bare entity
     *  - an Iterator
     *
     * @param mixed $data
     * @return LayerAccessProcessor
     *
     */
    public function insert($data)
    {
        if (is_array($data)){
            $result = new \ArrayIterator($data);
        } elseif (is_a($data, '\App\Model\Lib\Layer')) {
            $result = new \ArrayIterator($data->toArray());
        } elseif (is_a($data, '\Iterator'))  {
            $result = $data;
        } else {
            $result = new \ArrayIterator([$data]);
        }
        $this->AppendIterator->append($result);
        return $this;
    }

    //<editor-fold desc="****************** LAYER ACCESS INTERFACE *******************">
    /**
     * Get the result as an array of entities
     *
     * @return array
     */
    public function toArray()
    {
        $this->evaluate();
        return iterator_to_array($this->ResultIterator);
    }

    /**
     * Count of records currently in AppendIterator
     *
     * @return int
     */
    public function rawCount()
    {
        return iterator_count($this->AppendIterator);
    }

    /**
     * Count of records currently in ResultIterator
     *
     * @return int
     */
    public function resultCount()
    {
        if($this->ResultIterator === FALSE) {
            $result = 0;
        } else {
            $result = iterator_count($this->ResultIterator);
        }
        return $result;
    }

    /**
     * Do final processing in for the various 'toXxxxx' methods
     *
     * The 5 'toXxxxx` methods return ResultArray. If it exists, and
     * AccessArgs has not changed it can be ResultArray can be
     * trusted as current and valid. Then we avoid reprocessing.
     */
    protected function evaluate()
    {
        if(is_null($this->AccessArgs)) {
            $this->AccessArgs = new LayerAccessArgs($this);
        }

        if($this->previousArgsTimestamp != $this->AccessArgs->getTimestamp()) {
            $this->ResultIterator = FALSE;
        }

        if($this->ResultIterator === FALSE) {
            $this->ResultIterator = $this->perform($this->AccessArgs);
        }
    }

    /**
     * Get the result as Layer object
     *
     * @return Layer
     */
    public function toLayer()
    {
        $result = $this->toArray();
        return layer($result, $this->entityClass);
    }

    /**
     * Get an array of values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toValueList($valueSource = null)
    {
        $this->evaluate();

        //this skips out if appenditerator is empty but hasn't been tested
        //and the need for this hasn't been verified
        $resultValueSource = FALSE;
        if (count($this->ResultIterator) > 0) {
            $this->AccessArgs->setAccessNodeObject('resultValue', $valueSource);
            $resultValueSource = $this->AccessArgs->accessNodeObject('resultValue');
        }

        if ($resultValueSource) {

            $result = collection($this->ResultIterator)
                ->reduce(function ($harvest, $entity) use ($resultValueSource){
                    if (!is_null($resultValueSource->value($entity))) {
                        array_push($harvest, $resultValueSource->value($entity));
                    }
                    return $harvest;
                }, []);
        } else {
            $result = [];
        }
        return $result;
    }

    /**
     * Get a key => value list
     *
     * @param $keySource string|ValueSource
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toKeyValueList($keySource = null, $valueSource = null)
    {
        $this->evaluate();

        //this skips out if appenditerator is empty but hasn't been tested
        //and the need for this hasn't been verified
        $resultValueSource = FALSE;
        $resultKeySource = FALSE;
        if (count($this->ResultIterator) > 0) {
            $this->AccessArgs->setAccessNodeObject('resultKey', $keySource);
            $resultKeySource = $this->AccessArgs->accessNodeObject('resultKey');
            $this->AccessArgs->setAccessNodeObject('resultValue', $valueSource);
            $resultValueSource = $this->AccessArgs->accessNodeObject('resultValue');
        }

        if ($resultKeySource && $resultValueSource) {
                $result = collection($this->ResultIterator)
                    ->reduce(function($harvest, $entity) use ($resultKeySource, $resultValueSource){
                        $harvest[$resultKeySource->value($entity)] = $resultValueSource->value($entity);
                        return $harvest;
                    }, []);
        } else {
            $result = [];
        }
        return $result;
    }

    /**
     * Get a list of distinct values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toDistinctList($valueSource = null)
    {
        return array_unique($this->toValueList($valueSource));
    }

    /**
     * Get the stored registry instance
     *
     *
     * @return null|ValueSourceRegistry
     */
    public function getValueRegistry()
    {
        if(!is_null($this->getArgObj())) {
            $result = $this->getArgObj()->getValueRegistry();
        } else {
            $result = null;
        }
        return $result;
    }
    //</editor-fold>

    /**
     * Initiate a fluent Access definition
     *
     * @return LayerAccessArgs
     */
    public function find()
    {
        if(is_null($this->AccessArgs)) {
            $this->AccessArgs = new LayerAccessArgs($this);
            $this->ResultIterator = FALSE;
        }
        $this->AccessArgs->setLayer($this->layerName);
        return $this->AccessArgs;
    }

    /**
     * Run the Access process and return an iterator containing the result
     *
     * @param $argObj LayerAccessArgs
     * @return LayerAccessProcessor
     */
    public function perform($argObj)
    {
        $this->setArgObj($argObj);
        $this->AccessArgs->setLayer($this->layerName);
        $this->ResultIterator = $this->AppendIterator;

        if($this->AccessArgs->hasFilter()) {
            $this->ResultIterator = $this->performFilter();
        }

        if($this->AccessArgs->hasSort()) {
            $this->ResultIterator = $this->performSort();
        }

        if($this->AccessArgs->hasPagination()) {
            $this->ResultIterator = $this->performPagination();
        }

        if(is_array($this->ResultIterator)) {
            $this->ResultIterator = new \ArrayIterator($this->ResultIterator);
        }

        $this->previousArgsTimestamp = $this->AccessArgs->getTimestamp();

        return $this->ResultIterator;

    }

    /**
     * Filter the data based on AccessArgs settings
     *
     * @return array
     */
    protected function performFilter()
    {
        $argObj = $this->AccessArgs;
        $comparison = $argObj->selectComparison($argObj->valueOf('filterOperator'));

        $set = collection($this->ResultIterator);
        return $set->filter(function ($entity, $key) use ($argObj, $comparison) {
            $actual = $argObj->accessNodeObject('filter')->value($entity);
            return $comparison($actual, $argObj->valueOf('filterValue'));
        })->toArray();
    }

    /**
     * Sort the data based on AccessArgs settings
     *
     * @return array
     */
    protected function performSort()
    {
        $column = $this->AccessArgs->valueOf('sortColumn');
        $dir = $this->AccessArgs->valueOf('sortDir');
        $type = $this->AccessArgs->valueOf('sortType');
        $unsorted = new Collection($this->ResultIterator);
        $sorted = $unsorted->sortBy($column, $dir, $type)->toArray();
        //indexes are out of order and could be confusing
        return array_values($sorted);
    }

    /**
     * Paginate the data based on AccessArgs settings

     * @return array
     */
    protected function performPagination()
    {
        $page = $this->AccessArgs->valueOf('page');
        $limit = $this->AccessArgs->valueOf('limit');
        $unchuncked = new Collection($this->ResultIterator);
        $chunked = $unchuncked->chunk($limit)->toArray();
//        osd($chunked);
        if(isset($chunked[$page])) {
            $result = $chunked[$page];
        } else {
            $result = array_pop($chunked);
        }
        return $result;
    }

    /**
     * Store an the Access process instruction set
     *
     * @param $argObj LayerAccessArgs
     * @return bool
     */
    public function setArgObj($argObj)
    {
        $obj = clone $argObj;
        $obj->resetData();
        $this->AccessArgs = $obj;
        $this->ResultIterator = FALSE;
    }

    /**
     * Get a reference to the internal AccessArgs
     *
     * Consider using cloneArgObj() to avoid unanticipated value changes
     *
     * @return LayerAccessArgs A reference to the internal copy
     */
    public function getArgObj()
    {
        return $this->AccessArgs;
    }

    /**
     * Remove the internal AccessArgs object
     */
    public function clearAccessArgs()
    {
        $this->AccessArgs = null;
        $this->ResultIterator = FALSE;
    }

    /**
     * Get a clone of the Access instructions (with an empty data property)
     *
     * @return LayerAccessArgs
     */
    public function cloneArgObj()
    {
        $obj = clone $this->AccessArgs ?? new LayerAccessArgs();
        $obj->resetData();
        return $obj;

    }

    public function __debugInfo()
    {
        return [
            '[AppendIterator]' => isset($this->AppendIterator)
                ? 'Contains ' . $this->rawCount() . ' items.'
                : 'not set',
            '[AccessArgs]' => is_null($this->AccessArgs)
                ? 'null'
                : $this->AccessArgs,
            '[layerName]' => $this->layerName,
            '[ResultArray]' => $this->ResultIterator === FALSE
                ? 'FALSE'
                : 'Contains ' . $this->resultCount() . ' items.'
        ];
    }
}
