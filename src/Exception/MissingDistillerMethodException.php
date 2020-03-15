<?php
namespace App\Exception;

use Cake\Core\Exception\Exception;

class MissingDistillerMethodException extends Exception
{
	
    public function __construct($message, $code = 500, $previous = null)
    {
		
		/**
		 * When adding seeds to a layer
         * make sure there are distiller functions to back it up
		 */
		
        parent::__construct($message, $code, $previous);
    }
	
}
