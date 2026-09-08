<?php

namespace App\Coin\Domain;

interface CoinsRespository
{
    public function save(Coin $coin): void;
}
