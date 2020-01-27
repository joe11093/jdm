<?php

abstract class SearchStrategy
{
    /* ATTRIBUTES */
    protected $domain; // web site domain
    protected $options; // options during getting representation from domain
    protected $searchEntry; // search entry provided by user
    protected $representation; // representation of search entry as obtained from domain

    /* CONSTRUCTOR */
    protected function __construct($domain, $options, $searchEntry)
    {
        $this->domainRootPath = $domain;
        $this->options = $options;
        $this->searchEntry = $searchEntry;
    }

    /* METHODS */
    abstract public function constructRepresentation();
    abstract public function termExists();
    abstract public function getResult();
}
