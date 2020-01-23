<?php

class CsvReader
{
    protected $file;
 
    public function __construct($filePath) {
        $this->file = fopen($filePath, 'r');
    }
 
    public function rows()
    {
        while (!feof($this->file)) {
            $row = fgetcsv($this->file, 4096);
            
            yield $row;
        }
        
        return;
    }
}
$start = microtime(true);
$csv = new CsvReader('01012020-LEXICALNET-JEUXDEMOTS-ENTRIES.txt');
$i=0;
foreach ($csv->rows() as $row) {
    // Do something with the CSV row.
    $i++;
}
$time_elapsed_secs = microtime(true) - $start;
echo "time elapsed: ".$time_elapsed_secs;
echo $i;