<?php

use Phalcon\Mvc\MongoCollection;

class Messages extends MongoCollection
{
    public function initialize()
    {
        $this->setSource('messages');
    }
}
