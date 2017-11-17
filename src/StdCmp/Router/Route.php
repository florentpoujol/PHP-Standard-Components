<?php

namespace StdCmp\Router;

class Route
{
    /**
     * @var string[]
     */
    protected $methods = [];
    protected $paramNames = [];

    protected $rawUri = "";
    protected $regexUri = "";

    /**
     * @var callable
     */
    protected $action;

    protected $paramConstraints = [];
    protected $paramDefaults = [];

    /**
     * @param string|string[] $method
     */
    public function __construct($methods, string $uri, callable $action, array $paramConditions = [], array $paramDefaultValues = [])
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        $this->methods = array_map("strtolower", $methods);
        $this->action = $action;
        $this->paramDefaults = $paramDefaultValues;

        if ($paramConditions !== null) {
            $this->paramConstraints = $paramConditions;
        }
        $this->setUri($uri);
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(array $args = null): string
    {
        if ($args === null) {
            return $this->rawUri;
        }

        $uri = $this->rawUri;
        foreach ($args as $name => $value) {
            $uri = str_replace('{' . $name . '}', $value, $uri);
            $uri = str_replace("[$name]", $value, $uri);
        }

        // suppose that the remaining placeholders are not needed
        // so only return the uri up to the first bracket
        $pos = strpos($uri, "[");
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return $uri;
    }

    protected function setUri(string $uri)
    {
        $this->rawUri = $uri;

        // render all slashes optional, if the uri has a placeholder
        $pos = strpos($uri, "[");
        if ($pos !== false) {
            $subUri = substr($uri, $pos - 1); // part of the uri in which work on slashes
            $uri = str_replace(
                $subUri,
                str_replace("/", "/?", $subUri),
                $uri
            );
        }

        // make sure the uri ends with an optional trailing slash
        if (substr($uri, -2) !== "/?") {
            $uri .= "/?";
        }

        // look for optional placeholder
        $matches = [];
        if (preg_match_all("/\[([^\]]+)\]/", $uri, $matches) > 0) {
            foreach ($matches[1] as $id => $varName) {
                $this->paramNames[] = $varName;

                $constraint = "[^/&]+";
                if (isset($this->paramConstraints[$varName])) {
                    $constraint = $this->paramConstraints[$varName];
                }

                $uri = str_replace("[$varName]","($constraint)?", $uri);
            }
        }

        // look for named placeholder
        $matches = [];
        if (preg_match_all("/{([^}]+)}/", $uri, $matches) > 0) {
            foreach ($matches[1] as $id => $varName) {
                $this->paramNames[] = $varName;

                $constraint = "[^/&]+";
                if (isset($this->paramConstraints[$varName])) {
                    $constraint = $this->paramConstraints[$varName];
                }

                $uri = str_replace('{'.$varName.'}',"($constraint)", $uri);
            }
        }

        $this->regexUri = $uri;
    }

    public function getAction(): callable
    {
        return $this->action;
    }

    public function getParamConstraints(): array
    {
        return $this->paramConstraints;
    }

    public function getParamDefaults(): array
    {
        return $this->paramDefaults;
    }

    /**
     * Tell whether the route match the specified method and uri or not.
     */
    function match(string $method, string $uri): bool
    {
         return !in_array(strtolower($method), $this->methods) ||
             preg_match('~^' . $this->regexUri . '$~', $uri) === 1;
    }

    /**
     * Return the captured placeholders from the uri as an associative array.
     * Missing placeholders from the uri are not present at all in the returned array.
     */
    public function getParamsFromUri(string $uri): array
    {
        $matches = [];
        $assocMatches = [];
        if (preg_match("~^" . $this->regexUri . "$~", $uri, $matches) === 1) {
            if (count($matches) === 1) {
                // no placeholder capture, just the whole uri match
                return [];
            }

            array_shift($matches);
            foreach ($this->paramNames as $id => $name) {
                if ($matches[$id] === "") {
                    // if the uri miss some optional placeholders
                    // their captured value is empty string
                    break;
                }
                $assocMatches[$name] = $matches[$id];
            }
        }
        return $assocMatches;
    }
}
