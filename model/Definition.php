<?php
class Definition
{
    /* ATTRIBUTES */
    private $number; // number preceding content in <CODE>
    private $content; // content in <CODE>
    // TODO example might be added later

    /* CONSTRUCTOR */
    public function __construct($number, $content)
    {
        $this->number = $number;
        $this->content = $content;
    }

    /* METHODS */
    public function getNumber()
    {
        return $this->number;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
