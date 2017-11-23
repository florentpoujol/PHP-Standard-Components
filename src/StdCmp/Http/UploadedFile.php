<?php

namespace StdCmp\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    public function __construct(array $fileInfo = null)
    {
        if ($fileInfo !== null) {
            $this->fileInfo = $fileInfo;
        }
    }

    /**
     * @var array Data from the $_FILE superglobal
     */
    protected $fileInfo = [];

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream(): StreamInterface
    {
        // TODO: Implement getStream() method.
    }

    protected $fileMoved = false;

    public function moveTo($targetPath)
    {
        if ($this->fileMoved) {
            throw new \RuntimeException("File with name '" . $this->getClientFilename() . "' already moved.");
        }

        $error = $this->getError();
        if (empty($this->fileInfo) || $error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Cannot move the file as none was uploaded or there was an error during the upload. Error code: $error.");
        }

        $realPath = realpath($targetPath);
        if ($realPath === false) {
            throw new \InvalidArgumentException("Provided target path '$targetPath' is invalid.");
        }

        $tmpPath = $this->fileInfo["tmp_name"];
        if (move_uploaded_file($tmpPath, $realPath)) {
            $this->fileMoved = true;
            return;
        }

        throw new \RuntimeException("There was an error moving the file from '$tmpPath' to '$targetPath'");
    }

    public function getSize()
    {
        if (isset($this->fileInfo["size"])) {
            return (int)$this->fileInfo["size"];
        }
        return null;
    }

    public function getError(): int
    {
        return $this->fileInfo["error"] ?? UPLOAD_ERR_NO_FILE;
    }

    public function getClientFilename()
    {
        return $this->fileInfo["name"] ?? null;
    }

    public function getClientMediaType(): string
    {
        return $this->fileInfo["type"] ?? null;
    }
}
