<?php
namespace App\Filament\Resources\ImputationResource\Pages;
use App\Filament\Resources\ImputationResource;
use Filament\Resources\Pages\CreateRecord;
class CreateImputation extends CreateRecord { protected static string $resource = ImputationResource::class; protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); } }
