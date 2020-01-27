<?php
require_once(dirname(__FILE__, 3) . '/classLoader.php');

class SearchByRelatedTermStrategy extends ExactSearchStrategy
{
    /* ATTRIBUTES */
    private $relationTypeName; // the relation type between the search entry and its entering entities

    /* CONSTRUCTOR */
    public function __construct($domain, $options, $searchEntry, $relationTypeName)
    {
        parent::__construct($domain, $options, $searchEntry);
        $this->relationTypeName = $relationTypeName;
    }

    /* METHODS */
    public function relationTypeExists()
    {
        return array_key_exists($this->relationTypeName, Parser::RELATION_TYPES);
    }

    public function getResult()
    {
        if (!$this->relationTypeExists())
            return json_encode([
              'error' => 1,
              'message' => "No relation of type " . $this->relationTypeName . " exists"]);

        return parent::getResult();
    }
}
