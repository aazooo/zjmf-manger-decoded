<?php

namespace think\api;

use InvalidArgumentException;

class Group
{
    protected $name;
    protected $client;

    public function __construct(Client $client, $name = null)
    {
        $this->name   = $name;
        $this->client = $client;
    }

    public function request($method, $uri = '', $options = [])
    {
        if ($this->name) {
            $uri = $this->name . '/' . $uri;
        }
        return $this->client->request($method, $uri, $options);
    }

    protected function getRequestClass($method)
    {
        $className = ucfirst($method);
        if ($this->name) {
            $className = $this->name . "\\" . $className;
        }

        return "\\think\\api\\request\\" . $className;
    }

    public function __call($method, $params)
    {

        $reqClass = $this->getRequestClass($method);

        if (class_exists($reqClass)) {
            return new $reqClass($this, ...$params);
        }

        throw new InvalidArgumentException("Api {$method} not found!");
    }
}
