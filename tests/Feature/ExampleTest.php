<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * L'accueil est réservé aux membres : un visiteur est redirigé vers la connexion,
     * et la page de connexion répond.
     */
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk();
    }
}
