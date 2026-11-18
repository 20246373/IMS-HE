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
            ->blade('<x-required-note /><x-field label="Name" name="name" /><x-field label="Notes" name="notes" :required="false" />')
            ->assertSee('<span>*</span> Required', false)
            ->assertSee('<label for="name" class="required">Name</label>', false)
            ->assertSee('<label for="notes" class="optional">Notes</label>', false);
    }

    public function test_required_fields_are_marked_with_an_asterisk_without_a_legend_when_all_are_required(): void
    {
        // Log in: both fields required, so they carry the asterisk and no "* Required" legend is needed.
        $this->get(route('login', ['as' => 'customer']))->assertOk()
            ->assertSee('<label for="login" class="required">Email</label>', false)
            ->assertSee('<label for="login-password" class="required">Password</label>', false)
            ->assertDontSee('class="required-note"', false)
            ->assertDontSee('All fields are required');
    }
}
