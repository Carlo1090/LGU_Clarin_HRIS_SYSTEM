<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_portal_shows_the_latest_active_public_announcements(): void
    {
        $this->withoutVite();
        $this->seed();
        $this->travelTo(Date::parse('2026-11-24 04:00:00 UTC'));

        $older = Announcement::create(['title' => 'Earlier notice', 'content' => 'Earlier message', 'is_published' => true]);
        $this->travel(1)->hour();
        $latest = Announcement::create(['title' => 'New notice', 'content' => 'New message', 'is_published' => true]);

        foreach ([
            ['is_published' => false],
            ['starts_at' => now()->addHour()],
            ['ends_at' => now()],
            ['audience' => 'HR Officer'],
        ] as $attributes) {
            Announcement::create($attributes + ['title' => 'Hidden notice', 'content' => 'Hidden message', 'is_published' => true]);
        }

        foreach (Role::query()->get() as $role) {
            $this->actingAs(User::factory()->for($role)->create())
                ->get(route('dashboard'))->assertOk()
                ->assertInertia(fn ($page) => $page->component('dashboard')
                    ->has('announcements', 2)
                    ->where('announcements.0.id', $latest->id)
                    ->where('announcements.0.visible_at', '2026-11-24T05:00:00.000000Z')
                    ->where('announcements.1.id', $older->id)
                    ->where('canManageAnnouncements', in_array($role->name, ['Administrator', 'HR Officer', 'HRMO Head'], true)));
        }
    }

    public function test_hr_can_schedule_a_public_announcement_in_manila_time(): void
    {
        $this->withoutVite();
        $this->travelTo(Date::parse('2026-11-24 04:00:00 UTC'));
        $hr = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->create();
        $employee = User::factory()->for(Role::factory()->state(['name' => 'Employee']))->create();
        $store = route('hris.resources.store', ['resource' => 'announcements']);
        $data = [
            'title' => 'Office assembly',
            'content' => 'Please report to the municipal hall.',
            'starts_at' => '2026-11-24T13:00',
            'ends_at' => '2026-11-24T15:00',
            'is_published' => true,
        ];

        $this->actingAs($employee)->post($store, $data)->assertForbidden();
        $this->actingAs($hr)
            ->post($store, array_replace($data, ['ends_at' => '2026-11-24T12:00']))
            ->assertSessionHasErrors('ends_at');
        $this->post($store, $data + ['posted_by' => $employee->id, 'audience' => 'Employee'])
            ->assertSessionHasNoErrors()->assertRedirect(route('hris.resources.index', ['resource' => 'announcements']));

        $notice = Announcement::sole();
        $this->assertEquals($hr->id, $notice->posted_by);
        $this->assertSame('all', $notice->audience);
        $this->assertTrue($notice->starts_at->equalTo(Date::parse('2026-11-24 05:00:00 UTC')));

        $this->get(route('hris.resources.index', ['resource' => 'announcements']))
            ->assertInertia(fn ($page) => $page->where('records.data.0.starts_at', '2026-11-24T13:00'));

        $this->actingAs($employee)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->has('announcements', 0));

        $this->travel(1)->hour();
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->where('announcements.0.title', 'Office assembly')
            ->where('announcements.0.visible_at', '2026-11-24T05:00:00.000000Z'));
    }
}
