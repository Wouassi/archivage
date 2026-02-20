<?php
namespace App\Filament\Resources\DepenseResource\Pages;
use App\Filament\Resources\DepenseResource;
use Filament\Resources\Pages\CreateRecord;
class CreateDepense extends CreateRecord { protected static string $resource = DepenseResource::class; protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); } }
