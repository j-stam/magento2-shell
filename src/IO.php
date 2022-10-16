<?php

namespace Stam\Shell;

use DOMException;
use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv as CsvProcessor;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Xml\Generator as XmlGenerator;
use Magento\Framework\Xml\Parser as XmlParser;

class IO
{
    /**
     * @var CsvProcessor
     */
    protected $csvProcessor;

    /**
     * @var XmlParser
     */
    protected $xmlParser;

    /**
     * @var XmlGenerator
     */
    protected $xmlGenerator;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * IO constructor.
     *
     * @param CsvProcessor $csvProcessor
     * @param XmlParser $xmlParser
     * @param XmlGenerator $xmlGenerator
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        CsvProcessor $csvProcessor,
        XmlParser $xmlParser,
        XmlGenerator $xmlGenerator,
        JsonSerializer $jsonSerializer
    ) {
        $this->csvProcessor = $csvProcessor;
        $this->xmlParser = $xmlParser;
        $this->xmlGenerator = $xmlGenerator;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param $file
     * @param int $lineLength
     * @param string $delimiter
     * @param string $enclosure
     * @return array
     * @throws Exception
     */
    public function readCsv($file, $lineLength = 0, $delimiter = ',', $enclosure = '"')
    {
        $this->csvProcessor->setLineLength($lineLength)
            ->setDelimiter($delimiter)
            ->setEnclosure($enclosure);

        return $this->csvProcessor->getData($file);
    }

    /**
     * @param array $data
     * @param $file
     * @param string $delimiter
     * @param string $enclosure
     * @return $this
     * @throws FileSystemException
     */
    public function writeCsv(array $data, $file, $delimiter = ',', $enclosure = '"')
    {
        $this->csvProcessor->setDelimiter($delimiter)
            ->setEnclosure($enclosure);

        $this->csvProcessor->appendData($file, $data, 'w');

        return $this;
    }

    /**
     * @param $file
     * @return array|string
     */
    public function readXml($file)
    {
        $this->xmlParser->load($file);

        return $this->xmlParser->xmlToArray();
    }

    /**
     * @param array $data
     * @param $file
     * @return $this
     * @throws DOMException
     */
    public function writeXml(array $data, $file)
    {
        $this->xmlGenerator->arrayToXml($data);
        $this->xmlGenerator->save($file);

        return $this;
    }

    /**
     * @param $file
     * @return array|bool|float|int|mixed|string|null
     */
    public function readJson($file)
    {
        $json = file_get_contents($file);

        return $this->jsonSerializer->unserialize($json);
    }

    /**
     * @param array $data
     * @param $file
     * @return $this
     */
    public function writeJson(array $data, $file)
    {
        $json = $this->jsonSerializer->serialize($data);

        file_put_contents($file, $json);

        return $this;
    }
}
