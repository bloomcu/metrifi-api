<?php

namespace Tests\Feature\Http\Connections;

use DDD\Domain\Connections\Connection;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a wordpress connection', function () {
    $this->withoutExceptionHandling();

    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id
    ]);

    $this->actingAs($user);

    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'WordPress Website',
        'name' => 'My WordPress Site',
        'token' => [
            'wordpress_url' => 'https://example.com',
            'username' => 'admin',
            'app_password' => 'password123',
        ],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('connections', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'service' => 'WordPress Website',
        'account_name' => null,
        'name' => 'My WordPress Site',
    ]);

    $connection = Connection::latest()->first();
    expect($connection->token['wordpress_url'])->toBe('https://example.com');
    expect($connection->token['username'])->toBe('admin');
    expect($connection->token['app_password'])->toBe('password123');
});

it('validates wordpress connection request', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id
    ]);

    $this->actingAs($user);

    // Missing wordpress_url
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'WordPress Website',
        'name' => 'My WordPress Site',
        'token' => [
            'username' => 'admin',
            'app_password' => 'password123',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token.wordpress_url']);

    // Missing username
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'WordPress Website',
        'name' => 'My WordPress Site',
        'token' => [
            'wordpress_url' => 'https://example.com',
            'app_password' => 'password123',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token.username']);

    // Missing app_password
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'WordPress Website',
        'name' => 'My WordPress Site',
        'token' => [
            'wordpress_url' => 'https://example.com',
            'username' => 'admin',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token.app_password']);

    // Invalid URL
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'WordPress Website',
        'name' => 'My WordPress Site',
        'token' => [
            'wordpress_url' => 'invalid-url',
            'username' => 'admin',
            'app_password' => 'password123',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token.wordpress_url']);
});
