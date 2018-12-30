<?php

namespace SorokinFM;

class LoggerScopeManager {

    private $_scope = [];

    public function __construct($scope)
    {
        $this->_scope = $scope;
        /** @var ElasticSearchLogTarget $logger */
        $logger = \Yii::getLogger();
        foreach( $this->_scope as $key => $value ) {
            $logger->addToScope($key, $value);
        }
    }

    public function __destruct()
    {
        /** @var MyLogger $logger */
        $logger = \Yii::getLogger();
        foreach( $this->_scope as $key => $value ) {
            $logger->removeFromScope($key);
        }
    }
}
