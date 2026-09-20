<?php

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(
    TestCase::class,
    RefreshDatabase::class,
)->beforeEach(function () {
    Role::firstOrCreate(['name' => 'student']);
    Role::firstOrCreate(['name' => 'instructor']);
    Role::firstOrCreate(['name' => 'assistant']);
    Role::firstOrCreate(['name' => 'super_admin']);
})->in('Feature');
