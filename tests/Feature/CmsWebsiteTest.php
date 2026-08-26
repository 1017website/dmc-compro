<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\SiteContent;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\TemplateContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_uses_the_provided_template(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('DMC Pro | Mitra Bisnis PT Garam', false)
            ->assertSee('class="hero"', false)
            ->assertSee('id="inquiry-form"', false);
    }

    public function test_inquiry_form_stores_a_lead(): void
    {
        $this->postJson('/inquiry', [
            'name' => 'PT Contoh', 'email' => 'hello@example.com',
            'need' => 'Garam Industri', 'message' => 'Butuh penawaran.',
        ])->assertCreated();

        $this->assertDatabaseHas('inquiries', ['email' => 'hello@example.com', 'status' => 'new']);
    }

    public function test_developer_can_log_in_and_open_cms(): void
    {
        $this->seed();

        $this->post('/cms/login', ['email' => '1017website@gmail.com', 'password' => '1017Website2020.'])
            ->assertRedirect('/cms');
        $this->get('/cms')->assertOk()->assertSee('Ringkasan website');
        $this->get('/cms/content')->assertOk()->assertSee('Pilih bagian website')->assertSee('Portofolio Video');
        $this->get('/cms/content/hero')->assertOk()
            ->assertSee('Bagian website:')
            ->assertSee('Terjemahan opsional')
            ->assertSee('Simpan perubahan')
            ->assertDontSee('text.0006')
            ->assertDontSee('Aria Label')
            ->assertDontSee('▶');
        $this->get('/cms/branding')->assertOk()->assertSee('Logo Frontend')->assertSee('Logo CMS')->assertSee('Favicon');
        $this->get('/cms/users')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->every(fn (User $user) => $user->role !== 'developer'));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create(['is_active' => false]);
        $user = User::query()->first();
        $this->post('/cms/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_every_template_content_field_is_discoverable_and_overridable(): void
    {
        $service = app(TemplateContentService::class);
        $fields = $service->fields();
        $this->assertGreaterThan(100, count($fields));
        $this->assertContains('Hero', collect($fields)->pluck('group')->all());
        $this->assertNotContains('SEO', collect($fields)->pluck('group')->all());
        $this->assertFalse(collect($fields)->contains(fn (array $field) => str_contains($field['label'], 'Aria Label')));

        SiteContent::query()->create([
            'content_key' => 'text.0001', 'group_name' => 'SEO', 'label' => 'Title',
            'type' => 'text', 'value_id' => 'Judul DMC Pro Baru',
        ]);
        $this->assertStringContainsString('<title>Judul DMC Pro Baru</title>', $service->render());
    }

    public function test_branding_assets_can_be_uploaded_and_rendered(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->first());

        $this->put('/cms/branding', [
            'frontend_logo' => UploadedFile::fake()->image('frontend.png', 300, 120),
            'cms_logo' => UploadedFile::fake()->image('cms.png', 120, 120),
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
        ])->assertSessionHasNoErrors();

        $this->assertCount(3, SiteSetting::query()->whereIn('setting_key', ['frontend_logo', 'cms_logo', 'favicon'])->get());
        $this->get('/')->assertOk()->assertSee('/storage/branding/', false);
        $this->get('/cms')->assertOk()->assertSee('class="brand-logo"', false);
    }

    public function test_seo_settings_accept_the_index_follow_option(): void
    {
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->put('/cms/settings', [
            'seo_title' => 'DMC Pro | Mitra PT Garam, Chemical & Water Treatment',
            'seo_description' => 'DMC Pro menyediakan garam industri, chemical supply, dan industrial water treatment sebagai mitra bisnis PT Garam (Persero) untuk pasar lokal dan ekspor.',
            'seo_keywords' => 'garam industri, chemical supply, industrial water treatment',
            'seo_robots' => 'index, follow',
            'canonical_url' => 'https://dmcpro.co.id/',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', [
            'setting_key' => 'seo_robots',
            'value' => 'index, follow',
        ]);
    }

    public function test_user_creation_has_localized_validation_and_accepts_a_valid_password(): void
    {
        $this->seed();
        $developer = User::query()->where('role', 'developer')->firstOrFail();
        $this->actingAs($developer);

        $invalidResponse = $this->from('/cms/users/create')->post('/cms/users', [
            'name' => 'Admin DMC',
            'email' => 'admin@dmc.com',
            'role' => 'admin',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ]);

        $invalidResponse->assertRedirect('/cms/users/create')->assertSessionHasErrors('password');
        $this->assertSame('password minimal 10 karakter.', session('errors')->first('password'));

        $this->post('/cms/users', [
            'name' => 'Admin DMC',
            'email' => 'admin@dmc.com',
            'role' => 'admin',
            'password' => 'AdminDmc2026',
            'password_confirmation' => 'AdminDmc2026',
        ])->assertRedirect('/cms/users')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Admin DMC',
            'email' => 'admin@dmc.com',
            'role' => 'admin',
        ]);

        $this->get('/cms/users')
            ->assertOk()
            ->assertSee('admin@dmc.com')
            ->assertViewHas('users', fn ($users) => $users->every(fn (User $user) => $user->role !== 'developer'));
    }
}
