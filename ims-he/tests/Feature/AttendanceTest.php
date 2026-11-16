<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Payroll;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesPayrollData;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use CreatesPayrollData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 08:00:00');
    }

    // ---- access ----------------------------------------------------------------------------

    public function test_employee_supervisor_and_treasurer_can_open_attendance(): void
    {
        foreach ([User::EMPLOYEE, User::STORE_SUPERVISOR, User::TREASURER] as $role) {
            $this->actingAs($this->staff($role))->get('/attendance')->assertOk();
        }
    }

    public function test_other_roles_are_forbidden_and_guests_redirected(): void
    {
        foreach ([User::PRESIDENT, User::INVENTORY_CLERK, 'Administrator'] as $role) {
            $this->actingAs($this->staff($role))->get('/attendance')->assertForbidden();
        }

        $this->assertPendingIsSentToWaitingPage('/attendance');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/attendance')->assertRedirect(route('login'));
    }

    public function test_only_the_employee_role_can_clock_and_only_managers_can_correct(): void
    {
        $emp = $this->employee();
        $record = Attendance::create(['employee_id' => $emp->employee_id, 'work_date' => '2026-11-10', 'time_in' => '08:00:00']);

        foreach ([User::STORE_SUPERVISOR, User::TREASURER, User::PRESIDENT] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->post('/attendance/clock-in')->assertForbidden();
            $this->actingAs($user)->post('/attendance/clock-out')->assertForbidden();
        }

        $employeeUser = $this->staff(User::EMPLOYEE, $emp->employee_id);
        $this->actingAs($employeeUser)->get('/attendance/create')->assertForbidden();
        $this->actingAs($employeeUser)->get("/attendance/{$record->attendance_id}/edit")->assertForbidden();
        $this->actingAs($employeeUser)->put("/attendance/{$record->attendance_id}", ['time_in' => '09:00'])->assertForbidden();
        $this->actingAs($employeeUser)->post('/attendance', [])->assertForbidden();
    }

    // ---- clocking --------------------------------------------------------------------------

    public function test_employee_clocks_in_and_out_for_themselves(): void
    {
        $emp = $this->employee();
        $user = $this->staff(User::EMPLOYEE, $emp->employee_id);

        $this->actingAs($user)->post('/attendance/clock-in')->assertRedirect()->assertSessionHas('status');

        $record = Attendance::where('employee_id', $emp->employee_id)->firstOrFail();
        $this->assertSame('2026-11-16', $record->work_date->toDateString());
        $this->assertSame('08:00:00', $record->time_in);
        $this->assertNull($record->time_out);

        Carbon::setTestNow('2026-11-16 17:30:00');
        $this->actingAs($user)->post('/attendance/clock-out')->assertRedirect()->assertSessionHas('status');

        $record->refresh();
        $this->assertSame('17:30:00', $record->time_out);
        $this->assertEquals(1.5, (float) $record->overtime_hours);    // 9.5 hours worked, 1.5 beyond 8
    }

    public function test_only_one_record_per_employee_per_date(): void
    {
        $emp = $this->employee();
        $user = $this->staff(User::EMPLOYEE, $emp->employee_id);

        $this->actingAs($user)->post('/attendance/clock-in');
        $this->actingAs($user)->from('/attendance')->followingRedirects()->post('/attendance/clock-in')->assertSee('already clocked in today');

        $this->assertSame(1, Attendance::count());

        // The database refuses a second row too.
        $this->expectException(UniqueConstraintViolationException::class);
        Attendance::create(['employee_id' => $emp->employee_id, 'work_date' => '2026-11-16', 'time_in' => '09:00:00']);
    }

    public function test_cannot_clock_out_twice_or_without_clocking_in(): void
    {
        $emp = $this->employee();
        $user = $this->staff(User::EMPLOYEE, $emp->employee_id);

        $this->actingAs($user)->from('/attendance')->followingRedirects()->post('/attendance/clock-out')->assertSee('not clocked in today');

        $this->actingAs($user)->post('/attendance/clock-in');
        Carbon::setTestNow('2026-11-16 12:00:00');
        $this->actingAs($user)->post('/attendance/clock-out');
        $this->actingAs($user)->from('/attendance')->followingRedirects()->post('/attendance/clock-out')->assertSee('already clocked out');
    }

    public function test_employee_without_a_linked_record_cannot_clock_in(): void
    {
        $user = $this->staff(User::EMPLOYEE);

        $this->actingAs($user)->get('/attendance')->assertOk()->assertSee('not linked to an employee record');
        $this->actingAs($user)->from('/attendance')->followingRedirects()->post('/attendance/clock-in')->assertSee('not linked to an employee record');
        $this->assertSame(0, Attendance::count());
    }

    public function test_resigned_employee_cannot_clock_in(): void
    {
        $emp = $this->employee(['status' => 'Resigned']);

        $this->actingAs($this->staff(User::EMPLOYEE, $emp->employee_id))->from('/attendance')->followingRedirects()
            ->post('/attendance/clock-in')->assertSee('Resigned');
        $this->assertSame(0, Attendance::count());
    }

    // ---- overtime --------------------------------------------------------------------------

    public function test_overtime_is_hours_beyond_eight(): void
    {
        $this->assertSame(0.0, AttendanceService::overtimeHours('08:00:00', '16:00:00'));   // exactly 8
        $this->assertSame(0.0, AttendanceService::overtimeHours('08:00:00', '15:00:00'));   // 7, never negative
        $this->assertSame(0.5, AttendanceService::overtimeHours('08:00:00', '16:30:00'));
        $this->assertSame(4.25, AttendanceService::overtimeHours('06:00', '18:15'));
        $this->assertSame(0.0, AttendanceService::overtimeHours('08:00:00', null));          // still clocked in
        $this->assertSame(9.5, AttendanceService::hoursWorked('08:00', '17:30'));
    }

    // ---- view and filter -------------------------------------------------------------------

    public function test_employee_sees_only_their_own_records_and_managers_see_all(): void
    {
        $a = $this->employee(['first_name' => 'Alpha', 'last_name' => 'Aaa']);
        $b = $this->employee(['first_name' => 'Bravo', 'last_name' => 'Bbb']);
        $this->attend($a, '2026-11-02', '2026-11-03');
        $this->attend($b, '2026-11-02', '2026-11-03');

        $own = $this->actingAs($this->staff(User::EMPLOYEE, $a->employee_id))->get('/attendance');
        $own->assertOk();
        $this->assertCount(2, $own->viewData('records'));
        $own->assertDontSee('Bravo Bbb');

        foreach ([User::STORE_SUPERVISOR, User::TREASURER] as $role) {
            $all = $this->actingAs($this->staff($role))->get('/attendance');
            $this->assertCount(4, $all->viewData('records'));
            $all->assertSee('Alpha Aaa')->assertSee('Bravo Bbb');
        }
    }

    public function test_date_filter_limits_records_and_is_validated(): void
    {
        $emp = $this->employee();
        $this->attend($emp, '2026-10-28', '2026-11-05');
        $supervisor = $this->staff(User::STORE_SUPERVISOR);

        $r = $this->actingAs($supervisor)->get('/attendance?from=2026-11-01&to=2026-11-03');
        $this->assertSame(['2026-11-03', '2026-11-02', '2026-11-01'], $r->viewData('records')->pluck('work_date')->map->toDateString()->all());

        // Default: current month up to today (2026-11-16).
        $default = $this->actingAs($supervisor)->get('/attendance');
        $this->assertSame('2026-11-01', $default->viewData('from'));
        $this->assertCount(5, $default->viewData('records'));

        $this->actingAs($supervisor)->get('/attendance?from=2026-11-10&to=2026-11-01')->assertSessionHasErrors('to');
        $this->actingAs($supervisor)->get('/attendance?from=2026-11-10')->assertSessionHasErrors('to');
    }

    public function test_filter_form_shows_required_note(): void
    {
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->get('/attendance')
            ->assertSee('<span class="req">*</span> Required field', false)
            ->assertSee('name="from"', false)->assertSee('name="to"', false);
    }

    // ---- corrections -----------------------------------------------------------------------

    public function test_supervisor_and_treasurer_can_correct_a_record_and_overtime_is_recomputed(): void
    {
        $emp = $this->employee();

        foreach ([User::STORE_SUPERVISOR, User::TREASURER] as $i => $role) {
            $record = Attendance::create(['employee_id' => $emp->employee_id, 'work_date' => '2026-11-0' . ($i + 2), 'time_in' => '08:00:00']);
            $user = $this->staff($role);

            $this->actingAs($user)->get("/attendance/{$record->attendance_id}/edit")->assertOk();
            $this->actingAs($user)->put("/attendance/{$record->attendance_id}", ['time_in' => '07:30', 'time_out' => '18:00'])
                ->assertRedirect()->assertSessionHas('status');

            $record->refresh();
            $this->assertSame('07:30:00', $record->time_in);
            $this->assertSame('18:00:00', $record->time_out);
            $this->assertEquals(2.5, (float) $record->overtime_hours);
            $this->assertTrue(AuditLog::where(['action' => 'Corrected', 'table_name' => 'attendance', 'record_id' => $record->attendance_id, 'user_id' => $user->user_id])->exists());
        }
    }

    public function test_correction_needs_time_out_after_time_in(): void
    {
        $emp = $this->employee();
        $record = Attendance::create(['employee_id' => $emp->employee_id, 'work_date' => '2026-11-02', 'time_in' => '08:00:00']);

        $this->actingAs($this->staff(User::TREASURER))->put("/attendance/{$record->attendance_id}", ['time_in' => '08:00', 'time_out' => '07:00'])
            ->assertSessionHasErrors('time_out');
        $this->actingAs($this->staff(User::TREASURER))->put("/attendance/{$record->attendance_id}", ['time_in' => ''])
            ->assertSessionHasErrors('time_in');

        $this->assertNull($record->refresh()->time_out);
    }

    public function test_manager_can_add_a_missing_record_but_not_a_duplicate_date(): void
    {
        $emp = $this->employee();
        $user = $this->staff(User::STORE_SUPERVISOR);
        $data = ['employee_id' => $emp->employee_id, 'work_date' => '2026-11-09', 'time_in' => '08:00', 'time_out' => '17:00'];

        $this->actingAs($user)->post('/attendance', $data)->assertRedirect()->assertSessionHas('status');
        $this->assertSame(1, Attendance::count());
        $this->assertEquals(1.0, (float) Attendance::first()->overtime_hours);

        $this->actingAs($user)->from('/attendance/create')->followingRedirects()->post('/attendance', $data)->assertSee('already has an attendance record');
        $this->assertSame(1, Attendance::count());

        $this->actingAs($user)->post('/attendance', ['work_date' => '2026-12-31'])
            ->assertSessionHasErrors(['employee_id', 'time_in', 'work_date']);
    }

    public function test_add_and_correct_forms_mark_required_and_optional_fields(): void
    {
        $emp = $this->employee();
        $record = Attendance::create(['employee_id' => $emp->employee_id, 'work_date' => '2026-11-02', 'time_in' => '08:00:00']);
        $user = $this->staff(User::TREASURER);

        foreach (['/attendance/create', "/attendance/{$record->attendance_id}/edit"] as $url) {
            $this->actingAs($user)->get($url)->assertOk()
                ->assertSee('<span class="req">*</span> Required field', false)
                ->assertSee('Time out <span class="opt">(optional)</span>', false);
        }
    }

    public function test_dates_inside_a_finalized_payroll_are_locked(): void
    {
        $emp = $this->employee();
        $record = Attendance::create(['employee_id' => $emp->employee_id, 'work_date' => '2026-11-05', 'time_in' => '08:00:00', 'time_out' => '17:00:00']);
        $treasurer = $this->staff(User::TREASURER);
        Payroll::create(['period_start' => '2026-11-01', 'period_end' => '2026-11-15', 'processed_by' => $treasurer->user_id, 'status' => 'Final']);

        $this->actingAs($treasurer)->from('/attendance')->followingRedirects()
            ->put("/attendance/{$record->attendance_id}", ['time_in' => '07:00', 'time_out' => '20:00'])->assertSee('locked');
        $this->assertSame('08:00:00', $record->refresh()->time_in);

        $this->actingAs($treasurer)->from('/attendance/create')->followingRedirects()
            ->post('/attendance', ['employee_id' => $emp->employee_id, 'work_date' => '2026-11-06', 'time_in' => '08:00'])->assertSee('locked');
        $this->assertSame(1, Attendance::count());
    }
}
