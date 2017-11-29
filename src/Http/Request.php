<?php

namespace StdCmp\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

abstract class Request extends Message implements RequestInterface
{
    protected function populateFromGlobals()
    {
        parent::populateFromGlobals();

        $this->method = $_SERVER["REQUEST_METHOD"];

        $scheme = isset($_SERVER["HTTPS"]) ? "https" : "http";

        $port = $_SERVER["SERVER_PORT"];
        if (($scheme === "http" && $port === "80") || ($scheme === "https" && $port === "443")) {
            $port = "";
        }
        if ($port !== "") {
            $port .= ":$port";
        }

        $uri = new Uri("$scheme://$_SERVER[HTTP_HOST]$port$_SERVER[REQUEST_URI]");

        $this->host = $_SERVER["HTTP_HOST"];
    }

    protected $requestTarget = "/";

    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget): self
    {
        $oldRequestTarget = $this->requestTarget;

        // todo: make sure to handle the diferent kind of request target and when the argument non-string
        $this->requestTarget = $requestTarget;
        $newRequest = clone $this;

        $this->requestTarget = $oldRequestTarget;
        return $newRequest;
    }

    protected $method = "";

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method): self
    {
        $oldMethod = $this->method;

        $methods = ["get", "head", "post", "put", "delete", "connect", "options", "trace", "patch"];
        if (!in_array(strtolower($method), $methods)) {
            throw new \InvalidArgumentException("'$method' is not a valid HTTP method. Valid values are: " . implode(", ", $methods));
        }

        $this->method = $method;
        $newRequest = clone $this;

        $this->method = $oldMethod;
        return $newRequest;
    }

    /**
     * @var UriInterface
     */
    protected $uri;

    protected $host = "";

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $oldUri = $this->uri;
        $oldHost = $this->host;

        // todo: do stuff with uri and host
        $this->uri = $uri;

        $newRequest = clone $this;

        $this->uri = $oldUri;
        $this->host = $oldHost;
        return $newRequest;
    }
}
