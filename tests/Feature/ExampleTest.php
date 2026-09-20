<?php

test('guests are redirected to login from the home page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login', absolute: false));
});
