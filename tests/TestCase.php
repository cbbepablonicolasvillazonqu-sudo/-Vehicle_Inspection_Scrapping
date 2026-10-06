<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ninguna prueba escribe en los discos reales, aunque se olvide de
        // simularlos: si no, cada corrida deja fotos y contratos de prueba
        // mezclados con los de verdad.
        Storage::fake('privado');
        Storage::fake('public');
    }
}
