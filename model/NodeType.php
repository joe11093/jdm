<?php
class NodeType
{
    /* ATTRIBUTES */
    private $symbol; // nt
    private $id; // ntid
    private $name; // ntname

    /* CONSTRUCTOR */
    public function __construct($symbol, $id, $name)
    {
        $this->symbol = $symbol;
        $this->id = $id;
        $this->name = $name;
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

    public function toArray()
    {
        return get_object_vars($this);
    }
}
