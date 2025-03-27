<?php

use DDD\Domain\Connections\Connection;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a google analytics connection', function () {
    $this->withoutExceptionHandling();

    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id
    ]);

    $this->actingAs($user);

    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'Google Analytics - Property',
        'account_name' => 'Test Account',
        'name' => 'My GA Property',
        'uid' => 'ga-property-123',
        'token' => [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 3600,
        ],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('connections', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'service' => 'Google Analytics - Property',
        'account_name' => 'Test Account',
        'name' => 'My GA Property',
        'uid' => 'ga-property-123',
    ]);

    $connection = Connection::latest()->first();
    expect($connection->token['access_token'])->toBe('test-access-token');
    expect($connection->token['refresh_token'])->toBe('test-refresh-token');
    expect($connection->token['expires_in'])->toBe(3600);
});

it('validates google analytics connection request', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id
    ]);

    $this->actingAs($user);

    // Missing service
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'account_name' => 'Test Account',
        'name' => 'My GA Property',
        'uid' => 'ga-property-123',
        'token' => [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service']);

    // Missing name
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'Google Analytics - Property',
        'account_name' => 'Test Account',
        'uid' => 'ga-property-123',
        'token' => [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);

    // Missing uid
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'Google Analytics - Property',
        'account_name' => 'Test Account',
        'name' => 'My GA Property',
        'token' => [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['uid']);

    // Missing token
    $response = $this->postJson("/api/{$organization->slug}/connections", [
        'service' => 'Google Analytics - Property',
        'account_name' => 'Test Account',
        'name' => 'My GA Property',
        'uid' => 'ga-property-123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});
