<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz redirige al panel (y este, sin sesión, al login).
     */
    public function test_la_raiz_redirige_al_panel(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/panel');
    }
}
