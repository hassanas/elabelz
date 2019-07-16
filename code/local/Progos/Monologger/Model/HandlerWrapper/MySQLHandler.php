<?php


use Monolog\Logger;
use \MySQLHandler\MySQLHandler;

class Progos_Monologger_Model_HandlerWrapper_MySQLHandler extends Progos_Monologger_Model_HandlerWrapper_AbstractHandler {

    public function __construct(array $args) {

        $this->_validateArgs($args);
        $this->_handler = new MySQLHandler(
                $args['pdo'], $args['table'], $args['additionalFields'], $args['level'], $args['bubble']
        );
    }

    protected function _validateArgs(array &$args) {
        parent::_validateArgs($args);
        $config = Mage::getConfig()->getResourceConnectionConfig('default_setup');
        $hostname = $config->host;
        $user = $config->username;
        $password = $config->password;
        $dbname = $config->dbname;
        $pdo = new PDO("mysql:host=" . $hostname . ";dbname=" . $dbname, $user, $password);

        $args['pdo'] = $pdo;

        $args['table'] = 'monologs';

        $args['additionalFields'] = array('ip','datetime');
    }

}
