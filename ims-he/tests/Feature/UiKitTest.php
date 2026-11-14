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

    public function test_login_page_has_the_required_note_and_required_field_marker(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('All fields are required unless marked <span>(optional)</span>.', $content);
        $this->assertStringContainsString('class="required"', $content);
    }
}
