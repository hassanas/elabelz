<?php
use Monolog\Logger;
class Progos_Monologger_Model_Logwriter extends Zend_Log_Writer_Abstract
{
    /**
     * @var string
     */
    protected $_logFile = null;

    /**
     * @var Monolog\Logger
     */
    protected $_logger = null;

    /**
     * Array used to map Zend's log levels into Monolog's
     *
     * @var array
     */
    protected $_levelMap = array();

    public function __construct($logFile)
    {
        $this->_logFile = $logFile;

        Mage::dispatchEvent('add_spl_autoloader');

        if (Mage::getStoreConfigFlag('dev/monologger/enabled')) {

            $this->_logger = new Logger('monolog');
            $this->__initLevelMapping();
            $handlers = Mage::getStoreConfig('monologger/handlers');

            if (!is_null($handlers) && is_array($handlers)) {
                foreach ($handlers as $handlerModel => $handlerValues) {
                    $isActive = Mage::getStoreConfigFlag('dev/monologger/enabled');
                    if (!$isActive) {
                        continue;
                    }

                    $args = array();
                    $args['level'] = Mage::getStoreConfig('dev/monologger/loglevel');
                    $args['bubble'] = Mage::getStoreConfigFlag('monologger/handlers/' . $handlerModel . '/bubble');

                    $handlerWrapper = new Progos_Monologger_Model_HandlerWrapper_MySQLHandler($args);

                    if (array_key_exists('formatter', $handlerValues)
                        && array_key_exists('class', $handlerValues['formatter'])) {
                        $class = new ReflectionClass('\\Monolog\Formatter\\' . $handlerValues['formatter']['class']);
                        $formatter = $class->newInstanceArgs($handlerValues['formatter']['args']);
                        $handlerWrapper->setFormatter($formatter);
                    }

                    $this->_logger->pushHandler($handlerWrapper->getHandler());
                }
            }
        }
    }

    /**
     * Initialize the array used to map Zend's log levels into Monolog's
     */
    private function __initLevelMapping()
    {
        $this->_levelMap = array(
            Zend_Log::EMERG     => Logger::EMERGENCY,
            Zend_Log::ALERT     => Logger::ALERT,
            Zend_Log::CRIT      => Logger::CRITICAL,
            Zend_Log::ERR       => Logger::ERROR,
            Zend_Log::WARN      => Logger::WARNING,
            Zend_Log::NOTICE    => Logger::NOTICE,
            Zend_Log::INFO      => Logger::INFO,
            Zend_Log::DEBUG     => Logger::DEBUG,
        );
    }

    protected function _write($event)
    {
        $mageLog= new Zend_Log_Writer_Stream($this->_logFile);
        $mageLog->_write($event);
        if (Mage::getStoreConfigFlag('dev/monologger/enabled')) {
            $logfile = substr($this->_logFile, strrpos($this->_logFile, '/') + 1);
            if (Mage::getStoreConfig('dev/log/exception_file') == $logfile) {
                $event['priority'] = 2;
            }
            $level = $this->_levelMap[$event['priority']];
            $message = $event['message'];
            $args = ['ip' => Mage::helper('monologger')->getClientIP(), 'datetime' =>  Mage::getModel('core/date')->gmtDate()];
            $this->_logger->addRecord($level, $message, $args);
        }
    }


    static public function factory($config)
    {
        return new self(self::_parseConfig($config));
    }

}
	 