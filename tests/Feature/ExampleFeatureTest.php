<?php

test('root endpoint works', function () {
    $response = $this->get('/api/auth/login');

    $response->assertStatus(200);
});
