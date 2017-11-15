<?php

namespace StdCmp\Session;

class NativeSession implements Session
{
    public function start(array $options = []): bool
    {
        return session_start($options);
    }

    public function destroy(): bool
    {
        $this->deleteAll();
        return session_destroy();
    }

    public function setId(string $newId): bool
    {
        session_id($newId);
        return true;
    }

    public function getId(): string
    {
        return session_id();
    }

    public function regenerateId(bool $deleteOldSession = false): string
    {
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get(string $key, $defaultValue = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
    }

    public function delete(string $key)
    {
        unset($_SESSION[$key]);
    }

    public function deleteAll()
    {
        $_SESSION = [];
    }

    public function addFlashMessage(string $key, string $message)
    {
        if (! isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        $_SESSION[$key][] = $message;
    }

    public function getFlashMessages(string $key): array
    {
        $msgs = $this->get($key, []);
        $this->delete($key);
        return $msgs;
    }
}
