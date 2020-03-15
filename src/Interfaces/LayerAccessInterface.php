<?php


namespace App\Interfaces;


use App\Model\Lib\ValueSource;
use App\Model\Lib\ValueSourceRegistry;

/**
 * LayerAccessInterface
 *
 * This interface establishes the way LA objects that are meant to act as data end-points
 * must return their contents.
 *
 * @package App\Interfaces
 */
interface LayerAccessInterface
{

    /**
     * Get the result as an array of entities
     *
     * @return array
     */
    public function toArray();

    /**
     * Get the result as Layer object
     *
     * @return Layer
     */
    public function toLayer();

    /**
     * Get an array of values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toValueList($valueSource = null);

    /**
     * Get a key => value list
     *
     * @param $keySource string|ValueSource
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toKeyValueList($keySource = null, $valueSource = null);

    /**
     * Get a list of distinct values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toDistinctList($valueSource = null);

    /**
     * Get the stored registry instance
     *
     * @return ValueSourceRegistry
     */
    public function getValueRegistry();

}
