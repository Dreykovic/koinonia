<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_valid_member(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->exists);
        $this->assertTrue($user->hasRole(PlatformRole::MEMBRE));
    }
}
