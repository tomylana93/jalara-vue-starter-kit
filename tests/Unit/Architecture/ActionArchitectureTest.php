<?php

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Inertia\Inertia;

arch('new domain actions are final application classes')
    ->expect([
        'App\Actions\Authorization',
        'App\Actions\Profile',
    ])
    ->classes()
    ->toBeFinal();

arch('actions do not depend on transport presentation types')
    ->expect([
        'App\Actions\Authorization',
        'App\Actions\Profile',
    ])
    ->not->toUse([
        Command::class,
        Request::class,
        Inertia::class,
    ]);
