<?php

namespace StdCmp\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    public function __construct(string $uri)
    {
        $this->parseUri($uri);
    }

    protected function parseUri(string $uri)
    {
        // scheme:[//[user[:password]@]host[:port]]/path[?query][#fragment]
        // here, the scheme is considered optional too

        $scheme = "(?:(?P<scheme>[a-z0-9\.+-]+):)?";
        $userInfo = "(?:(?P<userInfo>[^:@]+(?::[^@]+)?)@)?";
        $authority = "(?://$userInfo(?P<host>[^/:]+)(?::(?P<port>[0-9]+))?)?"; // this does not support semicolon in hosts (so no IPv6)
        $path = "(?P<path>/?[^?]*)";
        $query = "(?:\?(?P<query>[^#]+))?";
        $fragment = "(?:#(?P<fragment>.+))?";

        $pattern = "~^" . $scheme . $authority . $path . $query . $fragment . "$~i";
        $matches = [];

        if (preg_match($pattern, $uri, $matches) === 1) {
            $this->scheme = $matches["scheme"];
            $this->userInfo = $matches["userInfo"];
            $this->host = $matches["host"];
            $this->port = null;
            if ($matches["port"] !== "") {
                $this->port = (int)$matches["port"];
            }
            $this->path = $matches["path"];
            $this->query = $matches["query"];
            $this->fragment = $matches["fragment"];
            return;
        }

        throw new \InvalidArgumentException("Uri does not looks like an URI: '$uri'");
    }

    protected $scheme = "";

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = "";

        if ($this->userInfo !== "") {
            $authority .= $this->userInfo . "@";
        }

        $authority .= $this->host;

        if ($this->port !== null) {
            $authority .= ":" . $this->port;
        }

        return $authority;
    }

    protected $userInfo = "";

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    protected $host = "";

    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @var int
     */
    protected $port;

    public function getPort()
    {
        if (
            $this->port !== null &&
            $this->scheme !== ""
            // && post is not standard
        ) {
            // todo get standard ports per scheme
            return $this->port;
        }
        return null;
    }

    protected $path = "";

    public function getPath(): string
    {
        return $this->path;
    }

    protected $query = "";

    public function getQuery(): string
    {
        return $this->query;
    }

    protected $fragment = "";

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): self
    {
        return $this->getCloneWith("scheme", $scheme);
    }

    public function withUserInfo($user, $password = null): self
    {
        if ($password !== null) {
            $user .= ":$password";
        }
        return $this->getCloneWith("userInfo", $user);
    }

    public function withHost($host): self
    {
        return $this->getCloneWith("host", $host);
    }

    public function withPort($port): self
    {
        return $this->getCloneWith("port", $port);
    }

    public function withPath($path): self
    {
        return $this->getCloneWith("path", $path);
    }

    public function withQuery($query): self
    {
        return $this->getCloneWith("query", $query);
    }

    public function withFragment($fragment): self
    {
        return $this->getCloneWith("fragment", $fragment);
    }

    protected function getCloneWith(string $propertyName, $value): self
    {
        $old = $this->{$propertyName};

        $this->{$propertyName} = $value;
        $newUri = clone $this;

        $this->{$propertyName} = $old;
        return $newUri;
    }

    public function __toString()
    {
        $uri = "";

        if ($this->scheme !== "") {
            $uri .= "$this->scheme:";
        }

        $authority = $this->getAuthority();
        $uri .= $authority;

        if ($this->path !== "") {
            $path = $this->path;
            if ($authority !== "") {
                $path = "/" . ltrim($path, "/");
            }
            $uri .= $path;
        }

        if ($this->query !== "") {
            $uri .= "?$this->query";
        }

        if ($this->fragment !== "") {
            $uri .= "#$this->fragment";
        }

        return $uri;
    }
}
