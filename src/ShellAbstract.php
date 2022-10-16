<?php

namespace Stam\Shell;

use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv as CsvProcessor;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Xml\Generator as XmlGenerator;
use Magento\Framework\Xml\Parser as XmlParser;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var ConsoleOutput
     */
    protected $consoleOutput;

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
        $this->_validate();
        $this->_showHelp();

        $this->_construct();

        $this->initialize();

        if (method_exists($this, 'di')) {
            $reflection = new ReflectionMethod($this, 'di');
            $params = $reflection->getParameters();
            $arguments = [];
            foreach ($params as $param) {
                if (!class_exists($param->getType()->getName())) {
                    throw new RuntimeException(sprintf('Class "%s" not found', $param->getType()->getName()));
                }
                $arguments[] = $this->getInstance($param->getType()->getName());
            }
            call_user_func_array([$this, 'di'], $arguments);
        }
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        //
    }

    /**
     * Initialize shell objects
     *
     * @return void
     */
    protected function initialize()
    {
        $this->connection = $this->getInstance(ResourceConnection::class)->getConnection();

        $this->io = $this->createInstance(IO::class, [
            'csvProcessor' => $this->createInstance(CsvProcessor::class),
            'xmlParser' => $this->createInstance(XmlParser::class),
            'xmlGenerator' => $this->createInstance(XmlGenerator::class),
            'jsonSerializer' => $this->createInstance(JsonSerializer::class),
        ]);

        if (!isset($this->logFileName)) {
            $this->logFileName = $this->convertPascalCaseToLogFileName(get_class($this)) . '.log';
        }

        $this->logger = $this->createInstance(Logger::class, [
            'name' => get_class($this),
            'handlers' => [
                'system' => $this->createInstance(Logger\Handler::class, [
                    'filesystem' => $this->createInstance(FileDriver::class),
                    'filePath' => BP . $this->logFilePath,
                    'fileName' => $this->logFileName,
                ]),
            ],
        ]);

        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * Get Magento Root path
     *
     * @return string
     */
    protected function getRootPath()
    {
        if (is_null($this->rootPath)) {
            $directory = $this->getInstance(DirectoryList::class);
            $this->rootPath = $directory->getRoot();
        }

        return $this->rootPath;
    }

    /**
     * Set Magento app code
     *
     * @param string|null $code
     * @return $this
     * @throws LocalizedException
     */
    protected function setAppAreaCode($code = null)
    {
        if (!is_null($code)) {
            $this->appAreaCode = $code;
        }

        if (is_null($this->appAreaCode)) {
            $this->appAreaCode = Area::AREA_GLOBAL;
        }

        $appState = $this->getInstance(State::class);
        $appState->setAreaCode($this->appAreaCode);

        return $this;
    }

    /**
     * Set secure area
     *
     * @param bool $isSecure
     * @return $this
     */
    protected function setIsSecureArea($isSecure)
    {
        $registry = $this->getInstance(Registry::class);
        $registry->register('isSecureArea', $isSecure);

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
     * @param string $pascalString
     * @return string
     */
    protected function convertPascalCaseToLogFileName($pascalString)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', $pascalString)), '-');
    }

    /**
     * Run script
     *
     */
    abstract public function run();

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
     * @param bool $default
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
     * @template T
     * @param T $type
     * @return T
     */
    public function getInstance($type)
    {
        return $this->getObjectManager()->get($type);
    }

    /**
     * Create new object instance
     *
     * @template T
     * @param T $type
     * @param array $arguments
     * @return T
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
     * @param int $options
     */
    public function writeln($message, $options = OutputInterface::OUTPUT_NORMAL)
    {
        $this->consoleOutput->writeln($message, true, $options);
    }

    /**
     * @param $messages
     * @param bool $newLine
     * @param int $options
     */
    public function write($messages, $newLine = true, $options = OutputInterface::OUTPUT_NORMAL)
    {
        $this->consoleOutput->write($messages, $newLine, $options);
    }
}
