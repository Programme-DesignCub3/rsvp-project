<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminEmailRecipientResource;
use App\Filament\Resources\BannerResource;
use App\Filament\Resources\ContactMessageResource;
use App\Filament\Resources\EventResource;
use App\Filament\Resources\MemberCategoryResource;
use App\Filament\Resources\MemberResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_navigation_items_are_grouped(): void
    {
        $this->assertSame('Content', EventResource::getNavigationGroup());
        $this->assertSame(1, EventResource::getNavigationSort());
        $this->assertSame('Content', BannerResource::getNavigationGroup());
        $this->assertSame(2, BannerResource::getNavigationSort());

        $this->assertSame('Members', MemberResource::getNavigationGroup());
        $this->assertSame(1, MemberResource::getNavigationSort());
        $this->assertSame('Members', MemberCategoryResource::getNavigationGroup());
        $this->assertSame(2, MemberCategoryResource::getNavigationSort());

        $this->assertSame('Communication', ContactMessageResource::getNavigationGroup());
        $this->assertSame(1, ContactMessageResource::getNavigationSort());

        $this->assertSame('Settings', AdminEmailRecipientResource::getNavigationGroup());
        $this->assertSame(1, AdminEmailRecipientResource::getNavigationSort());
    }

    public function test_admin_dashboard_renders_navigation_groups_in_order(): void
    {
        $admin = User::factory()->create(['email' => 'dean@designcub3.com']);

        $this->withoutVite();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSeeInOrder([
                'Content',
                'Members',
                'Communication',
                'Settings',
            ]);
    }
}
