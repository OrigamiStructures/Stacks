<?php
namespace App\Exception;

use Cake\Core\Exception\Exception;

/**
 * MissingStackTableRootException
 * 
 * Each stack table implementation must identify which layer is the 
 * primary layer for the stack. This is the tip-of-the-iceberg layer that 
 * necessarily has only one element.
 * 
 * Additionaly, it must be one of the layers listed in the schema for 
 * the table and must be a column of type = layer
 * 
 */
class MissingStackTableRootException extends Exception
{
	
    public function __construct($message, $code = 500, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
	
}
