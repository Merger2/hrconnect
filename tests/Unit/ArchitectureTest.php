<?php

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Model;

arch('services use strict types')
    ->expect('App\Services')
    ->toUseStrictTypes()
    ->not->toUse(['dd', 'dump']);

arch('jobs use strict types')
    ->expect('App\Jobs')
    ->toUseStrictTypes();

arch('controllers extend base Controller')
    ->expect('App\Http\Controllers')
    ->toExtend(Controller::class);

arch('no dd or dump in app code')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'exit'])
    ->ignoring('App\Services\GeminiClient');

arch('models extend Eloquent Model')
    ->expect('App\Models')
    ->toExtend(Model::class)
    ->ignoring(KnowledgeBase::class);

arch('enums are backed enums')
    ->expect('App\Enums')
    ->toImplement(BackedEnum::class);
