<?php
require_once(dirname(__FILE__, 3) . '/classLoader.php');

class TermPaginator extends Paginator
{
    /* ATTRIBUTES */
    private $orientation; // "entering" or "exiting" relations of a term

    /* CONSTRUCTOR */
    public function __construct($pageIndex, $perPage, $cached, $orientation)
    {
        parent::__construct($pageIndex, $perPage, $cached);
        $this->orientation = $orientation;
    }

    /* METHODS */
    public function getOrientation()
    {
        return $this->orientation;
    }

    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;
    }

    public function getDefaultPaginationSchema()
    {
        $schema = new stdClass(); // term schema root

        // no pagination required for these elements
        $schema->term = $this->cachedTerm->term; // getting the term
        $schema->rts = $this->cachedTerm->rts; // getting its relation types


        // 5 definitions per page per relation type by default
        $this->pageIndex = 1;
        $this->perPage = 5;

        $schema->defs = $this->getDefinitionsPaginationSchema();

        /* UNCOMMENT IF INITIAL PAGES OF DEFINITIONS =/= THOSE OF RELATIONS */
        // $this->pageIndex = <value>;
        // $this->perPage = <value>;

        // use the same pagination defaults used for definitions
        foreach ($this->cachedTerm->rts->types as $rt)
        {
            if (isset($this->cachedTerm->{"rt_".$rt->id}))
            {
                $schema->{"rt_".$rt->id} = new stdClass();
                $schema->{"rt_".$rt->id}->count = $this->cachedTerm->{"rt_".$rt->id}->count;

                $pagesSchema = $this->getRelationsPaginationSchema($rt->id);

                $schema->{"rt_".$rt->id}->{$this->orientation} = $pagesSchema->{$this->orientation};
            }
        }

        return $schema;
    }

    public function getDefinitionsPaginationSchema()
    {
        $schema = new stdClass(); // definition schema root
        $schema->count = $this->cachedTerm->defs->count;

        $offset = ($this->pageIndex - 1) * $this->perPage;

        $upper = min($this->perPage + $offset, count($this->cachedTerm->defs->definitions));

        $definitions = [];
        for ($i = $offset; $i < $upper; $i++)
            array_push($definitions, $this->cachedTerm->defs->definitions[$i]);

        $schema->definitions = $definitions;

        return $schema;
    }

    public function getRelationsPaginationSchema($type)
    {
        $schema = new stdClass();
        $schema->{$this->orientation} = new stdClass();
        $schema->{$this->orientation}->count = $this->cachedTerm->{"rt_".$type}->{$this->orientation}->count;
        $schema->{$this->orientation}->relations = $this->getRelationsPerPage($type);

        return $schema;
    }

    private function getRelationsPerPage($type)
    {
        $relations = [];
        $offset = ($this->pageIndex - 1) * $this->perPage;
        $upper = min($this->perPage + $offset, count($this->cachedTerm->{"rt_".$type}->{$this->orientation}->relations));

        for ($i = $offset; $i < $upper; $i++)
          array_push($relations, $this->cachedTerm->{"rt_".$type}->{$this->orientation}->relations[$i]);

        return $relations;
    }
}
