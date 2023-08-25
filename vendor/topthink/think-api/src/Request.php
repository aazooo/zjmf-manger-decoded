<?php

namespace think\api;

use ArgumentCountError;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;
use think\api\concerns\ObjectAccess;
use think\helper\Str;

abstract class Request
{
    use ObjectAccess;

    public $method = "POST";

    public $uri;

    /**
     * @var array The original parameters of the request.
     */
    public $data = [];

    public $options = [];

    protected $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    public function resolveOptions()
    {
        if ($this->method == 'GET') {
            $this->options['query'] = $this->data;
        } else {
            $this->options['form_params'] = $this->data;
        }
    }

    public function resolveUri()
    {
        if (empty($this->uri)) {
            $this->uri = Str::snake(class_basename(static::class), "/");
        }
    }

    public function request()
    {
        $this->resolveOptions();
        $this->resolveUri();

        try {
            return $this->group->request($this->method, $this->uri, $this->options);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                throw new Exception($response->getStatusCode(), $response->getBody()->getContents());
            }
            throw $e;
        }
    }

    public function __call($name, $arguments)
    {
        if (strncmp($name, 'with', 4) === 0) {
            $parameter = Str::camel(mb_strcut($name, 4));

            $value = $this->getCallArguments($name, $arguments);

            $this->data[$parameter] = $value;

            return $this;
        }

        throw new RuntimeException('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }

    /**
     * @param string $name
     * @param array $arguments
     * @param int $index
     *
     * @return mixed
     */
    private function getCallArguments($name, array $arguments, $index = 0)
    {
        if (!isset($arguments[$index])) {
            throw new ArgumentCountError("Missing arguments to method $name");
        }

        return $arguments[$index];
    }
}
