<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');

class Paginator
{
    /* ATTRIBUTES */
    private $pageIndex; // the index of the page to display
    private $perPage; // the number of elements per page
    private $cachedTerm; // the cached JSON term

    /* CONSTRUCTOR */
    public function __construct($pageIndex, $perPage, $cachedTerm)
    {
        $this->pageIndex = $pageIndex;
        $this->perPage = $perPage;
        $this->cachedTerm = $cachedTerm;
    }

    /* METHODS */
    public function getInitialPagesSchema()
    {
        $schema = new stdClass();
        $schema->term = $this->cachedTerm->term;
        $schema->rts = $this->cachedTerm->rts;

        $schema->defs = new stdClass();
        $schema->defs->count = $this->cachedTerm->defs->count;

        $oldIndex = $this->pageIndex; // save old value to restore after initial schema
        $oldPerPage = $this->perPage; // save old value to restore after initial schema

        // 5 definitions per page per relation type
        $this->pageIndex = 1;
        $this->perPage = 5;

        $schema->defs->definitions = $this->getDefinitionsPageSchema();

        /* UNCOMMENT IF INITIAL PAGES OF DEFINITIONS =/= THOSE OF RELATIONS */
        // $this->pageIndex = <value>;
        // $this->perPage = <value>;
        foreach($this->cachedTerm->rts as $rt)
        {
            $schema->{"rt_".$rt->id} = new stdClass();
            $schema->{"rt_".$rt->id}->count = $this->cachedTerm->{"rt_".$rt->id}->count;
            $schema->{"rt_".$rt->id}->relations = $this->getRelationsPageSchema($rt->id);
        }

        $this->pageIndex = $oldIndex;
        $this->perPage = $oldPerPage;

        return $schema;
    }

    public function getDefinitionsPageSchema()
    {
        $offset = ($this->pageIndex - 1) * $this->perPage;

        $upper = min($this->perPage + $offset, count($this->cachedTerm->defs->definitions));

        $definitions = [];
        for ($i = $offset; $i < $upper; $i++)
          array_push($definitions, $this->cachedTerm->defs->definitions[$i]);

        return $definitions;
    }

    public function getRelationsPageSchema($type)
    {
        $offset = ($this->pageIndex - 1) * $this->perPage;

        $upper = min($this->perPage + $offset, count($this->cachedTerm->{"rt_".$type}->relations));

        $relations = [];
        for ($i = $offset; $i < $upper; $i++)
          array_push($relations, $this->cachedTerm->{"rt_".$type}->relations[$i]);

        return $relations;
    }
}
