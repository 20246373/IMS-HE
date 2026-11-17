<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiKitTest extends TestCase
{
    public function test_layout_renders_a_success_alert_for_flash_status(): void
    {
        $this->withSession(['status' => 'Saved successfully.'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('alert-success')
            ->assertSee('role="alert"', false)
            ->assertSee('Saved successfully.');
    }

    public function test_layout_renders_validation_errors_in_an_error_alert(): void
    {
        $this->from(route('login'))
            ->post(route('login'), ['login' => '', 'password' => ''])
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('alert-error')
            ->assertSee('role="alert"', false)
            ->assertSee('<ul>', false)
            ->assertSee('The login field is required.');
    }

    public function test_required_note_and_required_field_marker_render(): void
    {
        $this->withViewErrors([])
            ->blade('<x-required-note /><x-field label="Name" name="name" />')
            ->assertSee('All fields are required unless marked <span>(optional)</span>.', false)
            ->assertSee('class="required"', false);
    }

    public function test_short_login_form_skips_the_required_note(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('All fields are required');
    }
}
