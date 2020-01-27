<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');

class Cache
{
    /* ATTRIBUTES */
    private $root; // cache root directory
    private $term; // name of the search entry, to be replaced by the actual parsed element upon caching
    private $pathComponents; // names of cached term's parent directories
    private $committed; // json object of term to be constructed by the policy and later cached
    private $policy; // caching policy

    /* CONSTRUCTOR */
    public function __construct($root, $pathComponents, $term)
    {
        $this->root = $root;
        $this->pathComponents = $pathComponents;
        $this->term = $term;

        if(!is_dir($root))
            mkdir($root);
    }

    /* METHODS */
    public function getRoot()
    {
        return $this->root;
    }

    public function getPathComponents()
    {
        return $this->pathComponents;
    }

    public function setPathComponents($pathComponents)
    {
        $this->pathComponents = $pathComponents;
    }

    public function getTerm()
    {
        return $this->term;
    }

    public function setTerm($term)
    {
        $this->term = $term;
    }

    public function getCommitted()
    {
        return $this->committed;
    }

    public function getPolicy()
    {
        return $this->policy;
    }

    public function setPolicy($policy)
    {
        $this->policy = $policy;
    }

    public function getParentDirectory()
    {
        $path =  "{$this->root}/";
        $path .= implode("/", $this->pathComponents);

        return $path;
    }

    public function getPath()
    {
        $path = $this->getParentDirectory();

        if (gettype($this->term) == "string")
            $path .= "/{$this->term}.json";
        else
            $path .= "/{$this->term->getName()}.json";

        return $path;
    }

    public function containsValidCachedTerm()
    {
        $path = $this->getPath();

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

    public function commit() // called only after setting the policy
    {
        $this->policy->commitTerm();
        $this->policy->commitDefinitions();
        $this->policy->commitRelationTypes();
        $this->policy->commitRelations();
        $this->policy->commitRelatedTerms();

        $this->committed = $this->policy->getConstructed();

        return $this->committed;
    }

    public function save()
    {
        if(!is_dir($this->getParentDirectory()))
            mkdir($this->getParentDirectory(), 0777, true);
        file_put_contents($this->getPath(), json_encode($this->committed));
    }

    public function load()
    {
        if ($this->containsValidCachedTerm() != false)
            return json_decode(file_get_contents($this->getPath()));

        return null;
    }

    public function remove()
    {
        if ($this->containsValidCachedTerm() != false)
            return unlink($this->getPath());

        return false;
    }
}
