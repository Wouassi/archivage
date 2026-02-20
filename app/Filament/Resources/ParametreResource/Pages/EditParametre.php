<?php
namespace App\Filament\Resources\ParametreResource\Pages;
use App\Filament\Resources\ParametreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditParametre extends EditRecord { protected static string $resource = ParametreResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
