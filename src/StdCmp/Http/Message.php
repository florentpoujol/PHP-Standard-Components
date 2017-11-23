<?php

namespace StdCmp\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface
{
    protected function populateFromGlobals()
    {
        $protocol = explode("/", $_SERVER["SERVER_PROTOCOL"]);
        $this->protocolVersion = $protocol[1];

        // populate headers
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === "HTTP_") {
                $key = str_replace("HTTP_", "", $key);
                $key = str_replace("_", "-", $key);
                $this->headers[strtolower($key)] = explode(",", $value);
            }
        }
    }

    protected $protocolVersion = "1.1";

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): self
    {
        $oldVersion = $this->protocolVersion;

        $this->protocolVersion = $version;
        $request = clone $this;
        $this->protocolVersion = $oldVersion;

        return $request;
    }

    /**
     * @var array [string => string[]]
     */
    protected $headers = [];

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        foreach ($this->headers as $_name => $values) {
            if (strcasecmp($_name, $name) === 0) {
                return true;
            }
        }
        return false;
    }

    public function getHeader($name): array
    {
        foreach ($this->headers as $_name => $values) {
            if (strcasecmp($_name, $name) === 0) {
                return $values;
            }
        }
        return [];
    }

    public function getHeaderLine($name)
    {
        return implode(", ", $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $oldHeaders = $this->headers;

        // todo: check header name
        // todo: check header value
        // todo: throw InvalidArgException

        foreach ($this->headers as $_name => $_values) {
            if (strcasecmp($_name, $name) === 0) {
                unset($this->headers[$_name]);
                break;
            }
        }
        $this->headers[$name] = $value;
        $newMessage = clone $this;
        $this->headers = $oldHeaders;

        return $newMessage;
    }

    public function withAddedHeader($name, $value): self
    {
        $oldHeaders = $this->headers;

        // todo: check header name
        // todo: check header value
        // todo: throw InvalidArgException

        foreach ($this->headers as $_name => $_values) {
            if (strcasecmp($_name, $name) === 0) {
                // update the case of $name to the one already existing in the headers
                $name = $_name;
            }
        }

        if (!isset($this->headers[$name])) {
            $this->headers[$name] = [];
        }
        $this->headers[$name][] = $value;

        $newMessage = clone $this;
        $this->headers = $oldHeaders;

        return $newMessage;
    }

    public function withoutHeader($name)
    {
        $oldHeaders = $this->headers;

        foreach ($this->headers as $_name => $_values) {
            if (strcasecmp($_name, $name) === 0) {
                unset($this->headers[$_name]);
                break;
            }
        }

        $newMessage = clone $this;
        $this->headers = $oldHeaders;

        return $newMessage;
    }

    /**
     * @var StreamInterface
     */
    protected $body;

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $oldBody = $this->body;

        $this->body = $body;
        $newMessage = clone $this;

        $this->body = $oldBody;
        return $newMessage;
    }
}
