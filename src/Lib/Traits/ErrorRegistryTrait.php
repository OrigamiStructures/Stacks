<?php
namespace App\Lib\Traits;

use Cake\Error\Debugger;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ErrorRegistryTrait
 *
 * @author dondrake
 */
trait ErrorRegistryTrait {

	protected $_errors = [];
	
// <editor-fold defaultstate="collapsed" desc="ERROR MANAGEMENT">

	private function registerError($message) {
		$trace = collection(Debugger::trace(['start' => 2, 'format' => 'points']));
		$stack = $trace->reduce(function($accum, $node){
			
			$node = !is_array($node) ? ['file' => 'unknown', 'line' => 'unknown'] : $node;
			$namespace = explode('/', $node['file']);
			$file = array_pop($namespace);
			$folder = array_pop($namespace);
			$namespace = implode('/', $namespace);
			$accum[] = "Line {$node['line'] } in $folder/$file:\t$namespace";
			return $accum;
		}, []);
		$error = [$message, $stack];
		$this->_errors[] = $error;
		pr($error);
	}


	public function getErrors() {
		return $this->_errors;
	}

// </editor-fold>
	
}
