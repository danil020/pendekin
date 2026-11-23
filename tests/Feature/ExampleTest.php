<?php

test('the homepage renders the short link workspace', function () {
    $this->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertSee('Bagikan tautan.', false)
        ->assertSee('id="auth-form"', false)
        ->assertSee('id="create-form"', false)
        ->assertSee('id="stats-dialog"', false);
});
