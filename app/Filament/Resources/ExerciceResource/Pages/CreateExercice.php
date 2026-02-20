<?php
namespace App\Filament\Resources\ExerciceResource\Pages;
use App\Filament\Resources\ExerciceResource;
use Filament\Resources\Pages\CreateRecord;
class CreateExercice extends CreateRecord { protected static string $resource = ExerciceResource::class; protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); } }
