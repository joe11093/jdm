<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');

class Cache
{
    /* ATTRIBUTES */
    private $path; // cache root directory
    private $committed; // json object of term to be cached

    /* CONSTRUCTOR */
    public function __construct($path)
    {
        $this->path = $path;
        if(!is_dir($path))
        {
            mkdir($path);
            mkdir("$path/terms/weight", 0777, true);
            mkdir("$path/terms/alpha", 0777, true);
        }
    }

    /* METHODS */
    public function getPath()
    {
        return $this->path;
    }

    public function getCommitted()
    {
        return $this->committed;
    }

    public function containsValid($name, $category)
    {
        $path = $this->constructPath($name, $category);

        if (file_exists($path))
        {
            $timestamp = filemtime($path);
            $date = date("F d Y H:i:s.", $timestamp);

            if ($timestamp < strtotime('- 30 days'))
                return false;

            return $path;
        }

        return false;
    }

    public function constructPath($name, $category)
    {
        return $this->path."/terms/$category/$name.json";
    }

    public function commit($term)
    {
        $this->committed = new stdClass();
        $this->commitTerm($term);
        $this->commitDefinitions($term);
        $this->commitRelationTypes($term);
        $this->commitRelations($term);

        return $this->committed;
    }

    private function commitTerm($term)
    {
        $this->committed->term = $term->toArray();
    }

    private function commitDefinitions($term)
    {
        $this->committed->defs = new stdClass();
        $this->committed->defs->count = $term->getDefinitionsCount();
        $this->committed->defs->definitions = [];

        foreach($term->getDefinitions() as &$definition)
            array_push($this->committed->defs->definitions, $definition->toArray());
    }

    private function commitRelationTypes($term)
    {
        $this->committed->rts = [];

        foreach($term->getRelationTypes() as &$relationType)
            array_push($this->committed->rts, $relationType->toArray());
    }

    private function commitRelations($term)
    {
        foreach ($term->getRelationTypes() as &$relationType)
        {

          $this->committed->{"rt_".$relationType->getId()} = new stdClass();
          $relations = [];

          foreach($term->getRelations() as &$relation)
          {
              if ($relation->getType() == $relationType->getId())
                  array_push($relations, $relation->toArray());
          }

          $this->committed->{"rt_".$relationType->getId()}->count = count($relations);
          $this->committed->{"rt_".$relationType->getId()}->relations = $relations;
        }
    }

    public function save($name, $category)
    {
        file_put_contents($this->constructPath($name, $category), json_encode($this->committed));
    }

    public function load($name, $category)
    {
        if ($this->containsValid($name, $category) != false)
            return json_decode(file_get_contents($this->constructPath($name, $category)));

        return null;
    }
}
