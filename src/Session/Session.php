<?php

namespace StdCmp\Session;

interface Session
{
    public function start(): bool;
    public function destroy(): bool;
    public function setId(string $newId): bool;
    public function getId(): string;
    public function regenerateId(bool $deleteOldSession = false): string;

    /**
     * @param mixed $value
     */
    public function set(string $key, $value);
    public function has(string $key): bool;
    /**
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get(string $key, $defaultValue = null);
    public function delete(string $key);
    public function deleteAll();

    public function addFlashMessage(string $key, string $message);
    public function getFlashMessages(string $key): array;
}
