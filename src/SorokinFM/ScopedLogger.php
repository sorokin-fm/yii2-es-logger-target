<?php

namespace SorokinFM;

use yii\log\Logger;

/**
 * Class ScopedLogger
 *
 * Нам нужно управлять контекстом, т.е. задавать переменную один раз на самом верхнем уровне,
 * чтобы не дублировать эту работу на остальных уровнях
 */
class ScopedLogger extends Logger {

    private $_scope = [];

    public function addToScope($key, $value) {
        //echo "add to scope: {$key} => {$value}\n";
        $this->_scope[$key] = $value;
    }

    public function removeFromScope($key) {
        //echo "remove from scope: {$key}\n";
        unset($this->_scope[$key]);
    }

    public function getScope(){
        return $this->_scope;
    }

    public function log($level, $message, array $context = array()){
        if( is_array($message) ) {
            $scopedMessage = array_merge($this->_scope, $message );
        } else {
            $scopedMessage = array_merge($this->_scope, ['message' => $message] );
        }
        return parent::log($level, $scopedMessage, $context);
    }
}


