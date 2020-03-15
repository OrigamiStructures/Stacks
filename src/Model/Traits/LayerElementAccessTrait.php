<?php


namespace App\Model\Traits;

/**
 * LayerElementAccessTrait
 *
 * This trait adds data access features to classes that have an ID indexed
 * array available at ::getData().
 *
 * @package App\Model\Traits
 */
trait LayerElementAccessTrait
{

    /**
     * Get the count of stored entities
     *
     * @return integer
     */
    public function count() {
        return count($this->getData());
    }

    /**
     * Does the primary data or the named layer contain the id'd item?
     *
     * @param $id
     * @param string|null $layer
     * @return bool
     */
    public function hasId($id, $layer = null)
    {
        if(is_null($layer)) {
            $haystack = $this->getData();
        } else {
            $haystack = array_flip($IDs($layer));
        }
        return key_exists($id, $haystack);
    }

    /**
     * Return the id indexed array of entities
     * @return array
     */
    abstract function getData();

    /**
     * Return the list of IDs for the named layer or default structure
     *
     * null will get the ids of the stored entities.
     * Name a layer to get the ids of a layer store in the stored entities
     *
     * @param string|null $layer
     * @return array
     */
    abstract function IDs($layer = null);

    /**
     * Return the n-th stored element or element(ID)
     *
     * Data is stored in id-indexed arrays, but this method will let you
     * pluck the id's or n-th item out
     *
     * @param int $number Array index 0 through n or Id of element
     * @param boolean $byIndex LAYERACC_INDEX or LAYERACC_ID
     * @return Entity
     */
    public function element($key, $byIndex = LAYERACC_INDEX){
        $data = $this->getData();
        if ($byIndex) {
            $data = array_values($data);
            if (count($data) > $key) {
                $result = $data[$key];
            } else {
                $result = null;
            }
        } else {
            if (in_array($key, $this->IDs())) {
                $result = $data[$key];
            } else {
                $result = null;
            }
        }
        return $result;
    }

    /**
     * Convenience wrapper to return the first element
     *
     * @return Entity
     */
    public function shift() {
        return $this->element(0, LAYERACC_INDEX);
    }

}
