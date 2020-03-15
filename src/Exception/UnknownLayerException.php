<?php
namespace App\Exception;

use Cake\Core\Exception\Exception;

class UnknownLayerException extends Exception
{

    public function __construct($message, $code = 500, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
