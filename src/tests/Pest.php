<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->beforeEach(function () {
    \App\Models\Role::firstOrCreate(['name' => 'student']);
    \App\Models\Role::firstOrCreate(['name' => 'instructor']);
    \App\Models\Role::firstOrCreate(['name' => 'assistant']);
    \App\Models\Role::firstOrCreate(['name' => 'super_admin']);
})->in('Feature');
