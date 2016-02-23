<?php

namespace OCA\SingleSignOn;

/**
 * Class APIServerConnection
 * @author Dauba
 */
class APIServerConnection implements IAPIServerConnection {

    /**
     * API server connection
     *
     * @var connection
     */
    private $connection;
    
    /**
     * @param mixed 
     */
    public function __construct($serverUrl) {
        $this->connection = new \SoapClient('https://sso.cloud.edu.tw/ORG/service/SSOServiceX?wsdl');
    }
    
    /**
     * Get API server connextion
     *
     * @return connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
}   
