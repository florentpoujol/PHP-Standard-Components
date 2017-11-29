<?php

namespace StdCmp\Http;

use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    public function __construct(bool $populateFromGlobals = true)
    {
        if ($populateFromGlobals) {
            $this->populateFromGlobals();
        }
    }

    protected function populateFromGlobals()
    {
        parent::populateFromGlobals();

        $this->cookieParams = $_COOKIE;
        $this->queryParams = $_GET;

        if (strcasecmp($this->method, "post") === 0) {
            $this->parsedBody = $_POST;
        } else {
            $body = stream_get_contents(STDIN);
            if ($body !== false) {
                $this->parsedBody = $body;
            }
        }

        $this->populateUploadedFiles();
    }

    protected function populateUploadedFiles()
    {
        foreach ($_FILES as $formFieldName => $fileInfo) {
            if (is_array($fileInfo["name"])) {
                // the form field is an array
                $files = [];
                foreach ($fileInfo as $key => $values) {

                    foreach ($values as $id => $value) {
                        // id is not necessarily numeric if the field name is   my-form[avatar] for instance (formFieldName is my-form, id is avatar
                        if (!isset($files[$id])) {
                            $files[$id] = [];
                        }
                        $files[$id][$key] = $value;
                    }
                }

                $this->uploadedFiles[$formFieldName] = [];
                foreach ($files as $id => $file) {
                    $this->uploadedFiles[$formFieldName][$id] = new UploadedFile($file);
                }
                continue;
            }

            $this->uploadedFiles[$formFieldName] = new UploadedFile($fileInfo);
        }
    }

    public function getServerParams(): array
    {
        return $_SERVER;
    }

    protected $cookieParams = [];

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): self
    {
        $oldCookies = $this->cookieParams;

        $this->cookieParams = $cookies;
        $newRequest = clone $this;

        $this->cookieParams = $oldCookies;
        return $newRequest;
    }

    protected $queryParams = [];

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): self
    {
        $oldQueryParams = $this->queryParams;

        $this->queryParams = $query;
        $newRequest = clone $this;

        $this->queryParams = $oldQueryParams;
        return $newRequest;
    }

    /**
     * @var array Values are UploadedFileInterface
     */
    protected $uploadedFiles = [];

    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $old = $this->uploadedFiles;

        // todo: check structure and thow InvalidArgException
        $this->uploadedFiles = $uploadedFiles;
        $newRequest = clone $this;

        $this->uploadedFiles = $old;
        return $newRequest;
    }

    protected $parsedBody;

    public function getParsedBody()
    {
        $contentType = $this->getHeader("content-type")[0] ?? "";
        if (
            strcasecmp($this->method, "post") === 0 &&
            ($contentType === "application/x-www-form-urlencoded" ||
             $contentType === "multipart/form-data")
        ) {
            return $_POST;
        }

        // todo: parse body ?
        if (is_array($this->parsedBody) || is_object($this->parsedBody)) {
            return $this->parsedBody;
        }
        return null;
    }

    public function withParsedBody($data): self
    {
        $old = $this->parsedBody;

        if (!is_null($data) && !is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException("Data must be null, an array or an object.");
        }

        $this->parsedBody = $data;
        $newRequest = clone $this;

        $this->parsedBody = $old;
        return $newRequest;
    }

    protected $attributes = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): self
    {
        $old = $this->attributes;

        $this->attributes[$name] = $value;
        $newRequest = clone $this;

        $this->attributes = $old;
        return $newRequest;
    }

    public function withoutAttribute($name): self
    {
        $old = $this->attributes;

        unset($this->attributes[$name]);
        $newRequest = clone $this;

        $this->attributes = $old;
        return $newRequest;
    }
}
