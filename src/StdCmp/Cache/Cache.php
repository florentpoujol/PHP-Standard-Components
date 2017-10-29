<?php

namespace StdCmp\Cache;

use StdCmp\Cache\Interfaces;

class Cache implements Interfaces\Driver
{
    /**
     * @var Item[]
     */
    protected $savedItems = [];
    protected $deferedItems = [];

    /**
     * @var Interfaces\Driver[]
     */
    protected $drivers = [];

    // Driver SimpleCache interface

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $defaultValue = null)
    {
        $value = null;
        foreach ($this->drivers as $driver) {
            $value = $driver->get($key, $defaultValue);
            if ($value !== null) {
                break;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys, $defaultValue = null): array
    {
        $values = [];

        foreach ($this->drivers as $driver) {
            $_values = $driver->getMultiple($keys);
            $values = array_merge($values, $_values);
            $keys = array_diff($keys, array_keys($_values)); // only keep not-found keys
        }

        foreach ($keys as $key) {
            $values[$key] = $defaultValue;
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, $ttl)
    {
        foreach ($this->drivers as $driver) {
            $driver->set($key, $value, $ttl);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $values, $ttl)
    {
        foreach ($this->drivers as $driver) {
            $driver->setMultiple($values, $ttl);
        }
    }


    // common

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $has = isset($this->savedItems[$key]) || isset($this->deferedItems[$key]);

        if (!$has) {
            foreach ($this->drivers as $driver) {
                $has = $driver->has($key);
                if ($has) {
                    break;
                }
            }
        }

        return $has;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMultiple(array $keys): array
    {
        $hasKeys = [];

        foreach ($keys as $id => $key) {
            $has = isset($this->savedItems[$key]) || isset($this->deferedItems[$key]);
            if ($has) {
                $hasKeys[] = $key;
                unset($keys[$id]);
            }
        }

        $keys = array_values($keys);
        if (count($keys) > 0) {
            foreach ($this->drivers as $driver) {
                $hasKeys = array_merge($hasKeys, $this->hasMultiple($keys));
            }
        }

        return array_unique($hasKeys);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key)
    {
        foreach ($this->drivers as $driver) {
            $driver->delete($key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys)
    {
        foreach ($this->drivers as $driver) {
            $driver->deleteMultiple($keys);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        foreach ($this->drivers as $driver) {
            $driver->clear();
        }
    }

    /**
     * @param Interfaces\Driver $driver
     */
    public function addDriver(Interfaces\Driver $driver)
    {
        $this->drivers[] = $driver;
    }

    /**
     * @return Interfaces\Driver[]
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * @param Interfaces\Driver[] $drivers
     */
    public function setDrivers(array $drivers)
    {
        $this->drivers = [];
        foreach ($drivers as $driver) {
            if (!($driver instanceof Interfaces\Driver)) {
                throw new \UnexpectedValueException("Bad Driver");
            }
            $this->drivers[] = $drivers;
        }
    }
}
