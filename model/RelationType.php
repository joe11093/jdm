<?php
class RelationType
{
    /* ATTRIBUTES */
    private $symbol; // rt
    private $id; // rtid
    private $name; // trname
    private $description; // trgpname
    private $help; // rthelp

    /* CONSTRUCTOR */
    public function __construct($symbol, $id, $name, $description, $help)
    {
        $this->symbol = $symbol;
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->help = $help;
    }

    /* METHODS */
    public function getSymbol()
    {
        return $this->symbol;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getHelp()
    {
        return $this->help;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
