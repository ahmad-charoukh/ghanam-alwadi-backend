<?php

namespace App\Filament\Delivery\Resources\Orders\Pages;

use App\Filament\Delivery\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
