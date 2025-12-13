<?php

namespace App\DTO;

class ProductionFilter
{
    private bool $showAll = false;

    public function isShowAll(): bool
    {
        return $this->showAll;
    }

    public function setShowAll(bool $showAll): void
    {
        $this->showAll = $showAll;
    }
}
