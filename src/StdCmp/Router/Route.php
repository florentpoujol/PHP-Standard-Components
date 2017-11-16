<?php

namespace StdCmp\Router;

class Route
{
    /**
     * @var string[]
     */
    protected $methods = [];
    protected $paramNames = [];

    protected $uri = "";
    protected $regexUri = "";

    /**
     * @var callable
     */
    protected $target;

    protected $paramConditions = [];
    protected $paramDefaultValues = [];

    /**
     * @param string|string[] $method
     */
    public function __construct($methods, string $uri, callable $target, array $paramConditions = [], array $paramDefaultValues = [])
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        $this->methods = array_map("strtolower", $methods);
        $this->target = $target;
        $this->paramDefaultValues = $paramDefaultValues;

        if ($paramConditions !== null) {
            $this->paramConditions = $paramConditions;
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
            return $this->uri;
        }

        $uri = $this->uri;
        foreach ($args as $name => $value) {
            $uri = str_replace('{' . $name . '}', $value, $uri);
        }
        return $uri;
    }

    protected function setUri(string $uri)
    {
        $this->uri = $uri;

        // look for optional segments
        $matches = [];
        // use a while loop because segments are nested and preg_match_all doesn't match within nested captures
        while (preg_match("/\[(.+)\]/", $uri, $matches) === 1) {
            $uri = str_replace($matches[0], "(?:$matches[1])?", $uri);
        }

        // look for named placeholder
        $matches = [];
        if (preg_match_all("/{([^}]+)}/", $uri, $matches) > 0) {
            foreach ($matches[1] as $id => $varName) {
                $this->paramNames[] = $varName;

                if (!isset($this->paramConditions[$varName])) {
                    $this->paramConditions[$varName] = "[^/&]+";
                }

                $uri = str_replace(
                    '{' . $varName . '}',
                    "(" . $this->paramConditions[$varName] . ")",
                    $uri
                );
            }
        }

        if ($uri[strlen($uri) - 1] === "/") {
            // make trailing slash always optional
            $uri .= "?";
        }

        $this->regexUri = $uri;
    }

    /**
     * @return callable
     */
    public function getTarget(): callable
    {
        return $this->target;
    }

    /**
     * @return array
     */
    public function getParamConditions(): array
    {
        return $this->paramConditions;
    }

    /**
     * @return array
     */
    public function getParamDefaultValues(): array
    {
        return $this->paramDefaultValues;
    }

    /**
     * @return array|bool
     */
    public function getParamsFromUri(string $method, string $targetUri)
    {
        if (!in_array(strtolower($method), $this->methods)) {
            return false;
        }

        $matches = [];
        if (preg_match('#^' . $this->regexUri . '$#', $targetUri, $matches) === 1) {
            array_shift($matches);

            $assocMatches = [];
            foreach ($this->paramNames as $id => $name) {
                // if the uri has optional segments that are missing from the target URI
                // there will be less entries in matches than in paramNames
                // so fill the blancks with null for now
                // will be filled by default args
                $assocMatches[$name] = null;
                if (isset($matches[$id])) {
                    $assocMatches[$name] = $matches[$id];
                }
            }

            return $assocMatches;
        }
        return false;
    }
}
