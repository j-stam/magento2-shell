<?php

namespace Stam\Shell;

class IO
{
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * @var \Magento\Framework\Xml\Parser
     */
    protected $xmlParser;

    /**
     * @var \Magento\Framework\Xml\Generator
     */
    protected $xmlGenerator;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * IO constructor.
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Framework\Xml\Parser $xmlParser
     * @param \Magento\Framework\Xml\Generator $xmlGenerator
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\Xml\Parser $xmlParser,
        \Magento\Framework\Xml\Generator $xmlGenerator,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
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
     * @throws \Exception
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
     * @throws \Magento\Framework\Exception\FileSystemException
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
     * @throws \DOMException
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
