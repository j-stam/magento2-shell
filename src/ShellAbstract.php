<?php

namespace Stam\Shell;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;

abstract class ShellAbstract
{
    /**
     * Input arguments
     *
     * @var array
     */
    protected $_args = [];

    /**
     * Magento Root path
     *
     * @var string
     */
    protected $rootPath;

    /**
     * Magento application area code
     *
     * @var string
     */
    protected $appAreaCode;

    /**
     * Magento object manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * @var IO
     */
    protected $io;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $logFileName;

    /**
     * @var string
     */
    protected $logFilePath = '/var/log/shell/';

    /**
     * Initialize application and parse input parameters
     *
     */
    public function __construct()
    {
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $this->objectManager = $bootstrap->getObjectManager();

        $this->setAppAreaCode();

        $this->_parseArgs();
        $this->_construct();
        $this->_validate();
        $this->_showHelp();

        $this->connection = $this->getInstance(\Magento\Framework\App\ResourceConnection::class)->getConnection();

        $this->io = $this->createInstance(IO::class, [
            'csvProcessor' => $this->createInstance(\Magento\Framework\File\Csv::class),
            'xmlParser' => $this->createInstance(\Magento\Framework\Xml\Parser::class),
            'xmlGenerator' => $this->createInstance(\Magento\Framework\Xml\Generator::class),
            'jsonSerializer' => $this->createInstance(\Magento\Framework\Serialize\Serializer\Json::class),
        ]);

        if (!isset($this->logFileName)) {
            $this->logFileName = ltrim(
                    strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', get_class($this))),
                    '-'
                ) . '.log';
        }
        $this->logger = $this->createInstance(Logger::class, [
            'name' => get_class($this),
            'handlers' => [
                'system' => $this->createInstance(Logger\Handler::class, [
                    'filesystem' => $this->createInstance(\Magento\Framework\Filesystem\Driver\File::class),
                    'filePath' => BP . $this->logFilePath,
                    'fileName' => $this->logFileName,
                ]),
            ],
        ]);
    }

    /**
     * Get Magento Root path
     *
     * @return string
     */
    protected function getRootPath()
    {
        if (is_null($this->rootPath)) {
            $directory = $this->getInstance(\Magento\Framework\Filesystem\DirectoryList::class);
            $this->rootPath = $directory->getRoot();
        }

        return $this->rootPath;
    }

    /**
     * Set magento app code
     *
     * @param  null  $code
     * @return $this
     */
    protected function setAppAreaCode($code = null)
    {
        if (!is_null($code)) {
            $this->appAreaCode = $code;
        }

        if (is_null($this->appAreaCode)) {
            $this->appAreaCode = \Magento\Framework\App\Area::AREA_GLOBAL;
        }

        $appState = $this->getInstance(\Magento\Framework\App\State::class);
        $appState->setAreaCode($this->appAreaCode);

        return $this;
    }

    /**
     * Parse input arguments
     *
     * @return $this
     */
    protected function _parseArgs()
    {
        $current = null;
        foreach ($_SERVER['argv'] as $arg) {
            $match = [];
            if (preg_match('#^--([\w\d_-]{1,})$#', $arg, $match) || preg_match('#^-([\w\d_]{1,})$#', $arg, $match)) {
                $current = $match[1];
                $this->_args[$current] = true;
            } else {
                if ($current) {
                    $this->_args[$current] = $arg;
                } elseif (preg_match('#^([\w\d_]{1,})$#', $arg, $match)) {
                    $this->_args[$match[1]] = true;
                }
            }
        }

        return $this;
    }

    /**
     * Additional initialize instruction
     *
     * @return $this
     */
    protected function _construct()
    {
        return $this;
    }

    /**
     * Validate arguments
     *
     */
    protected function _validate()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            die('This script cannot be run from Browser. This is the shell script.');
        }
    }

    /**
     * Run script
     *
     */
    abstract public function run();

    /**
     * Check is show usage help
     *
     */
    protected function _showHelp()
    {
        if (isset($this->_args['h']) || isset($this->_args['help'])) {
            die($this->usageHelp());
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f script.php -- [options]

  -h            Short alias for help
  help          This help
USAGE;
    }

    /**
     * Retrieve argument value by name or false
     *
     * @param $name
     * @param  bool  $default
     * @return bool|mixed
     */
    public function getArg($name, $default = false)
    {
        if (isset($this->_args[$name])) {
            return $this->_args[$name];
        }

        return $default;
    }

    /**
     * @return ObjectManagerInterface|null
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Retrieve cached object instance
     *
     * @param  string  $type
     * @return mixed
     */
    public function getInstance($type)
    {
        return $this->getObjectManager()->get($type);
    }

    /**
     * Create new object instance
     *
     * @param  string  $type
     * @param  array  $arguments
     * @return mixed
     */
    public function createInstance($type, array $arguments = [])
    {
        return $this->getObjectManager()->create($type, $arguments);
    }

    /**
     * @return AdapterInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param $message
     */
    public function writeln($message)
    {
        $this->write($message, true);
    }

    /**
     * @param $message
     * @param  bool  $newLine
     * @return $this
     */
    public function write($message, $newLine = true)
    {
        echo $message;

        if ($newLine) {
            echo PHP_EOL;
        }

        return $this;
    }
}
