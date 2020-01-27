<?php
require_once(dirname(__FILE__, 3) . '/classLoader.php');

abstract class CachePolicy
{
    /* ATTRIBUTES */
    protected $term; // parsed term used to construct the constructed
    protected $constructed; // term to be constructed, and later committed and cached

    /* CONSTRUCTOR */
    public function __construct($term)
    {
        $this->term = $term;
        $this->constructed = new stdClass();
    }

    public function getTerm()
    {
        return $this->term;
    }

    public function setTerm($term)
    {
        $this->term = $term;
    }

    /* METHODS */
    abstract public function commitTerm();
    abstract public function commitDefinitions();
    abstract public function commitRelationTypes();
    abstract public function commitRelations();
    abstract public function commitRelatedTerms();

    public function getConstructed()
    {
        return $this->constructed;
    }
}
