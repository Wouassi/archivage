<?php
namespace App\Filament\Resources\DepenseResource\Pages;
use App\Filament\Resources\DepenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditDepense extends EditRecord { protected static string $resource = DepenseResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
