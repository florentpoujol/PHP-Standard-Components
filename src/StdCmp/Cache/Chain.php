<?php

namespace StdCmp\Cache;

class Chain implements Interfaces\SimpleCache
{
    /**
     * @var Interfaces\SimpleCache[]
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
    public function set(string $key, $value, $ttl = null)
    {
        foreach ($this->drivers as $driver) {
            $driver->set($key, $value, $ttl);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        foreach ($this->drivers as $driver) {
            $has = $driver->has($key);
            if ($has) {
                return true;
            }
        }

        return false;
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
    public function clear()
    {
        foreach ($this->drivers as $driver) {
            $driver->clear();
        }
    }

    /**
     * @param Interfaces\SimpleCache $driver
     */
    public function addDriver(Interfaces\SimpleCache $driver)
    {
        $this->drivers[] = $driver;
    }

    /**
     * @return Interfaces\SimpleCache[]
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * @param Interfaces\SimpleCache[] $drivers
     */
    public function setDrivers(array $drivers)
    {
        $this->drivers = [];
        foreach ($drivers as $driver) {
            if (!($driver instanceof Interfaces\SimpleCache)) {
                throw new \UnexpectedValueException("Bad Driver");
            }
            $this->drivers[] = $drivers;
        }
    }
}
