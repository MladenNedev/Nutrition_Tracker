<?php
namespace App\Core;

class Container 
{
    private array $items = [];

    public function set(string $id, mixed $value): void
    {
        $this->items[$id] = $value;
    }

    public function get(string $id): mixed
    {
        if (!array_key_exists($id, $this->items)) {
            throw new \RuntimeException("Service not found: $id");
        }
        return $this->items[$id];
    }
}