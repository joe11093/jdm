<?php
class EntriesCleaner
{
    /* ATTRIBUTES */
    private $data;
    private $dataSource;

    private const LINE_SEPARATOR = "\r\n";
    private const ENTRY_SEPARATOR = ";";

    /* CONSTRUCTOR */
    public function __construct($dataSource)
    {
        $this->dataSource = $dataSource;
        $this->data = [];
    }

    /* METHODS */
    public function extractData()
    {
        $data = file_get_contents($this->dataSource);

        $data = mb_convert_encoding($data, 'UTF-8',
        mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true));

        foreach($this->getDataEntries($data) as $data_entry)
            array_push($this->data, $data_entry[1]);

        return $this->data;
    }

    public function getDataEntries(&$data)
    {
        $line = strtok($data, self::LINE_SEPARATOR);

        while ($line !== false)
        {
            if ($this->isDataEntry($line))
                yield $this->extractDataEntry($line);

            $line = strtok(self::LINE_SEPARATOR);
        }
    }

    public function isDataEntry($line)
    {
        return preg_match("/^\d+" . self::ENTRY_SEPARATOR .
            "[^" . self::ENTRY_SEPARATOR . "]+" . self::ENTRY_SEPARATOR . "$/", $line);
    }

    public function extractDataEntry($line)
    {
        $matches = [];

        preg_match("/^\d+" . self::ENTRY_SEPARATOR .
            "([^" . self::ENTRY_SEPARATOR . "]+)" . self::ENTRY_SEPARATOR . "$/",
            $line,
          $matches);

        return $matches;
    }

    public function sortDataByFrLexicOrder()
    {
        $previousLocale = setlocale(LC_ALL, 0);

        if (setlocale(LC_ALL, "fr_FR.utf8") !== false)
        {
            uasort($this->data, function($entry1, $entry2) {
                return strcoll(strtolower($entry1), strtolower($entry2));
            });

            setlocale(LC_ALL, $previousLocale);
        }
    }

    public function process()
    {
        if (!file_exists($this->dataSource))
            die("Invalid data source \"{$this->dataSource}\" provided for autocompletion");

        $this->extractData();
        $this->sortDataByFrLexicOrder();

        $data = implode("\n", $this->data);
        file_put_contents(dirname(__FILE__, 2) . "/assets/sorted__" . basename($this->dataSource), $data);

        return $this->data;
    }
}

function secondsToDuration($seconds)
{
  return floor($seconds / 3600) . gmdate(':i:s', $seconds % 3600);
}

function measureEntriesCleanerMethod()
{
    // $cleaner = new EntriesCleaner(dirname(__FILE__, 2) . "/assets/01012020-LEXICALNET-JEUXDEMOTS-ENTRIES+ENG.txt"); // Took: 0:28:23
    $cleaner = new EntriesCleaner(dirname(__FILE__, 2) . "/assets/head.txt");

    $start = time();
    $dt = new DateTime("@$start");

    echo "Started cleaning on " . $dt->format('d/m/Y H:i:s') . "\n";
    $cleaner->process();

    $end = time();
    $dt = new DateTime("@$end");
    echo "Finished cleaning on " . $dt->format('d/m/Y H:i:s') . "\n";
    echo "Took: " . secondsToDuration($end - $start) . "\n";
}

measureEntriesCleanerMethod();
