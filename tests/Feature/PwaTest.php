<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_pagina_offline_disponible_sin_sesion(): void
    {
        $this->get('/offline')
            ->assertOk()
            ->assertSee('Sin conexión');
    }

    public function test_login_incluye_manifest_y_service_worker(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('manifest.webmanifest')
            ->assertSee('sw.js');
    }
}
