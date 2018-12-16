<?php

use Phalcon\Mvc\MongoCollection;

class Requests extends MongoCollection
{
    public function initialize()
    {
        $this->setSource('requests');
    }
}
