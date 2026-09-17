<?php

use Bale\Api\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');
