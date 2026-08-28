<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\PageView;
use App\Models\SiteContent;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\TemplateContentService;
use Database\Seeders\SiteContentTranslationSeeder;
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
            ->assertSee('<html lang="id">', false)
            ->assertSee("var preferred = 'id'", false)
            ->assertSee('class="hero"', false)
            ->assertSee('Bahan Baku Kimia untuk Industri', false)
            ->assertSee('50% garam industri dan 50% bahan baku kimia untuk industri.', false)
            ->assertSee('data-business="water" hidden aria-hidden="true"', false)
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
            ->assertSee('Background utama Hero')
            ->assertSee('1920 × 1080 piksel')
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
        $this->assertFalse(collect($fields)->contains(fn (array $field) => str_starts_with($field['key'], 'dynamic.business.water.')));

        SiteContent::query()->create([
            'content_key' => 'text.0001', 'group_name' => 'SEO', 'label' => 'Title',
            'type' => 'text', 'value_id' => 'Judul DMC Pro Baru',
        ]);
        $this->assertStringContainsString('<title>Judul DMC Pro Baru</title>', $service->render());
    }

    public function test_translation_seeder_populates_all_languages_without_overwriting_edits(): void
    {
        $this->seed(SiteContentTranslationSeeder::class);

        // 170, not 174: the four play-button captions are hidden now, so the seeder
        // skips them. Their translations stay in the JSON in case the caption returns.
        $this->assertSame(170, SiteContent::query()->count());
        $this->assertSame(0, SiteContent::query()
            ->whereNull('value_id')->orWhereNull('value_en')->orWhereNull('value_zh')
            ->count());

        $this->assertDatabaseHas('site_contents', [
            'content_key' => 'text.0037',
            'value_id' => 'Bahan Baku Kimia untuk Industri',
            'value_en' => 'Industrial Chemical Raw Materials',
            'value_zh' => '工业化工原料',
        ]);
        $this->assertDatabaseHas('site_contents', [
            'content_key' => 'text.0036',
            'value_id' => '50',
            'value_en' => '50',
            'value_zh' => '50',
        ]);
        $this->assertDatabaseHas('site_contents', [
            'content_key' => 'dynamic.business.chemical.eyebrow',
            'value_id' => '50% Portofolio · Bahan Baku Kimia untuk Industri',
            'value_en' => '50% Portfolio · Industrial Chemical Raw Materials',
            'value_zh' => '50% 业务占比 · 工业化工原料',
        ]);

        SiteContent::query()->where('content_key', 'text.0037')->update(['value_en' => 'Custom English']);
        $this->seed(SiteContentTranslationSeeder::class);

        $this->assertDatabaseHas('site_contents', [
            'content_key' => 'text.0037',
            'value_en' => 'Custom English',
        ]);
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
        $this->get('/')->assertOk()
            ->assertSee('/storage/branding/', false)
            ->assertSee('class="footer-brand"><img src="/storage/branding/', false);
        $this->get('/cms')->assertOk()->assertSee('class="brand-logo"', false);
    }

    public function test_video_heading_is_constrained_to_avoid_overlapping_the_video(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('.video-copy {', false)
            ->assertSee('overflow-wrap: anywhere', false)
            ->assertSee('font-size: clamp(34px, 2.9vw, 48px)', false);
    }

    public function test_video_portfolio_cards_are_aligned_and_footer_logo_has_contrast(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('grid-column: span 3', false)
            ->assertSee('.portfolio-mockup-card.is-featured {', false)
            ->assertSee('justify-self: stretch', false)
            ->assertSee('box-shadow: inset 0 4px 0 var(--red)', false)
            ->assertSee('.is-featured .portfolio-thumbnail { min-height: 0; aspect-ratio: 16 / 9; }', false)
            ->assertSee('.is-featured .portfolio-mockup-copy { min-height: 168px; padding: 22px; }', false)
            ->assertSee('filter: brightness(0) invert(1)', false);
    }

    public function test_hidden_system_copy_cannot_replace_gallery_or_video_close_icons(): void
    {
        SiteContent::query()->insert([
            [
                'content_key' => 'text.0251',
                'group_name' => 'Umum',
                'label' => 'Stale gallery content',
                'type' => 'text',
                'value_id' => 'Kontak',
                'value_en' => 'Contact',
                'value_zh' => '联系',
            ],
            [
                'content_key' => 'text.0252',
                'group_name' => 'Umum',
                'label' => 'Stale video content',
                'type' => 'text',
                'value_id' => '© 2026 PT. Dynamika Multi Compro',
                'value_en' => '© 2026 PT. Dynamika Multi Compro',
                'value_zh' => '© 2026 PT. Dynamika Multi Compro',
            ],
        ]);

        $this->get('/')->assertOk()
            ->assertSee('<button class="modal-close js-close-modal" type="button" aria-label="Tutup galeri">×</button>', false)
            ->assertDontSee('<button class="modal-close js-close-modal" type="button" aria-label="Tutup galeri">Kontak</button>', false)
            ->assertSee('<button class="modal-close js-close-modal" type="button" aria-label="Tutup video">×</button>', false)
            ->assertDontSee('<button class="modal-close js-close-modal" type="button" aria-label="Tutup video">© 2026 PT. Dynamika Multi Compro</button>', false)
            ->assertSee("galleryModalClose.textContent = '×'", false)
            ->assertSee("videoModalClose.textContent = '×'", false)
            ->assertSee('.modal-close::before {', false)
            ->assertSee('content: "×"', false)
            ->assertSee('font-size: 0', false);
    }

    public function test_hero_background_can_be_uploaded_from_content_cms_and_rendered(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->put('/cms/content', [
            'media' => [
                'dynamic__hero__background' => UploadedFile::fake()->image('hero-background.jpg', 1920, 1080),
            ],
        ])->assertSessionHasNoErrors();

        $background = SiteContent::query()
            ->where('content_key', 'dynamic.hero.background')
            ->value('value_id');

        $this->assertNotNull($background);
        $this->assertStringStartsWith('/storage/site-media/', $background);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $background));

        $this->get('/')->assertOk()
            ->assertSee('class="hero-media" style="background-image:', false)
            ->assertSee($background, false);
    }

    public function test_video_and_gallery_forms_make_the_media_source_choice_clear(): void
    {
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->get('/cms/content/video-utama')->assertOk()
            ->assertSee('Cukup pilih salah satu cara')
            ->assertSee('Sampul video')
            ->assertSee('Video company profile')
            ->assertSee('Upload file')
            ->assertSee('Gunakan URL')
            ->assertSee('bukan halaman YouTube/Google Drive');

        $this->get('/cms/content/portofolio-video')->assertOk()
            ->assertSee('Daftar Video Portofolio')
            ->assertSee('+ Tambah Video')
            ->assertSee('Jumlah item bebas');

        $this->get('/cms/content/galeri')->assertOk()
            ->assertSee('Daftar Foto Galeri')
            ->assertSee('+ Tambah Foto')
            ->assertSee('Jumlah item bebas');

        $service = app(TemplateContentService::class);
        $this->assertCount(6, $service->defaultMediaCollection('gallery'));
        $this->assertCount(4, $service->defaultMediaCollection('videos'));
    }

    public function test_gallery_and_video_collection_counts_are_adjustable(): void
    {
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->put('/cms/content', [
            'collection_name' => 'gallery',
            'collection_items' => [
                'photo-a' => ['source' => 'url', 'url' => 'https://cdn.example.com/a.jpg', 'title' => 'Foto A', 'meta' => 'Gudang'],
                'photo-b' => ['source' => 'url', 'url' => 'https://cdn.example.com/b.jpg', 'title' => 'Foto B', 'meta' => 'Distribusi'],
            ],
        ])->assertSessionHasNoErrors();

        $gallery = json_decode(SiteSetting::query()->where('setting_key', 'media_collection_gallery')->value('value'), true);
        $this->assertCount(2, $gallery);
        $this->get('/')->assertOk()->assertSee('https://cdn.example.com/a.jpg', false)->assertSee('https://cdn.example.com/b.jpg', false);

        $this->put('/cms/content', [
            'collection_name' => 'videos',
            'collection_items' => [
                'video-a' => ['source' => 'url', 'url' => 'https://cdn.example.com/a.mp4', 'title' => 'Video A', 'category' => 'Profil', 'description' => 'Video profil perusahaan.'],
                'video-b' => ['source' => 'url', 'url' => 'https://cdn.example.com/b.webm', 'title' => 'Video B', 'category' => 'Operasional', 'description' => 'Video kegiatan operasional.'],
                'video-c' => ['source' => 'url', 'url' => 'https://cdn.example.com/c.mov', 'title' => 'Video C', 'category' => 'Distribusi', 'description' => 'Video distribusi.'],
            ],
        ])->assertSessionHasNoErrors();

        $videos = json_decode(SiteSetting::query()->where('setting_key', 'media_collection_videos')->value('value'), true);
        $this->assertCount(3, $videos);
        $this->get('/cms/content')->assertOk()->assertSee('3 media');
        $this->get('/')->assertOk()->assertSee('https://cdn.example.com/c.mov', false);
    }

    public function test_new_collection_media_files_can_be_uploaded(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->put('/cms/content', [
            'collection_name' => 'gallery',
            'collection_items' => [
                'new-photo' => ['source' => 'upload', 'title' => 'Foto Baru', 'meta' => 'Operasional'],
            ],
            'collection_media' => [
                'new-photo' => UploadedFile::fake()->image('operasional.jpg', 1200, 800),
            ],
        ])->assertSessionHasNoErrors();

        $gallery = json_decode(SiteSetting::query()->where('setting_key', 'media_collection_gallery')->value('value'), true);
        $this->assertCount(1, $gallery);
        $this->assertStringStartsWith('/storage/site-media/collections/gallery/', $gallery[0]['url']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $gallery[0]['url']));

        $this->put('/cms/content', [
            'collection_name' => 'videos',
            'collection_items' => [
                'new-video' => ['source' => 'upload', 'title' => 'Video Baru', 'category' => 'Profil', 'description' => 'Profil terbaru.'],
            ],
            'collection_media' => [
                'new-video' => UploadedFile::fake()->create('profil.mp4', 100, 'video/mp4'),
            ],
        ])->assertSessionHasNoErrors();

        $videos = json_decode(SiteSetting::query()->where('setting_key', 'media_collection_videos')->value('value'), true);
        $this->assertStringStartsWith('/storage/site-media/collections/videos/', $videos[0]['url']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $videos[0]['url']));
    }

    public function test_collections_recover_a_missing_media_source_and_render_video_previews(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        Storage::disk('public')->put('site-media/collections/gallery/existing.jpg', 'image');
        $this->put('/cms/content', [
            'collection_name' => 'gallery',
            'collection_items' => [
                'default-photo' => ['source' => '', 'default_index' => 0, 'url' => '', 'title' => 'Foto Bawaan', 'meta' => 'Produksi'],
                'existing-photo' => ['source' => '', 'default_index' => '', 'url' => '/storage/site-media/collections/gallery/existing.jpg', 'title' => 'Foto Upload', 'meta' => 'Gudang'],
            ],
        ])->assertSessionHasNoErrors();

        $gallery = json_decode(SiteSetting::query()->where('setting_key', 'media_collection_gallery')->value('value'), true);
        $this->assertSame('default', $gallery[0]['source']);
        $this->assertSame('upload', $gallery[1]['source']);

        Storage::disk('public')->put('site-media/collections/videos/existing.mp4', 'video');
        $this->put('/cms/content', [
            'collection_name' => 'videos',
            'collection_items' => [
                'existing-video' => [
                    'source' => '', 'default_index' => '',
                    'url' => '/storage/site-media/collections/videos/existing.mp4',
                    'title' => 'Video Upload', 'category' => 'Profil', 'description' => 'Video terbaru.',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $videos = json_decode(SiteSetting::query()->where('setting_key', 'media_collection_videos')->value('value'), true);
        $this->assertSame('upload', $videos[0]['source']);

        $this->get('/')->assertOk()
            ->assertSee("document.createElement('video')", false)
            ->assertSee("preview.src=item.url", false)
            ->assertSee('window.setTimeout(restoreCollectionCopy,60)', false);
    }

    public function test_media_can_use_a_direct_url_and_return_to_the_builtin_version(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());
        $video = collect(app(TemplateContentService::class)->fields())
            ->first(fn (array $field) => $field['group'] === 'Video Utama' && $field['type'] === 'video');
        $formKey = str_replace('.', '__', $video['key']);

        $this->put('/cms/content', [
            'contents' => [$formKey => [
                'source' => 'url',
                'id' => 'https://cdn.example.com/company-profile.mp4',
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_contents', [
            'content_key' => $video['key'],
            'value_id' => 'https://cdn.example.com/company-profile.mp4',
        ]);

        Storage::disk('public')->put('site-media/company-profile.mp4', 'video');
        SiteContent::query()->where('content_key', $video['key'])
            ->update(['value_id' => '/storage/site-media/company-profile.mp4']);

        $this->put('/cms/content', [
            'contents' => [$formKey => ['source' => 'default']],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('site_contents', ['content_key' => $video['key']]);
        Storage::disk('public')->assertMissing('site-media/company-profile.mp4');
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
    public function test_frontend_uses_the_variable_webfont_and_no_unreadable_type(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Plus+Jakarta+Sans:wght@200..800', $html);
        $this->assertStringContainsString('--font-geist-sans: "Plus Jakarta Sans"', $html);

        // The stylesheet asks for weights like 570 and 730, which only resolve
        // against a variable font, so the axis request above has to stay.
        $this->assertStringContainsString('font-weight: 570', $html);

        preg_match_all('/font-size:\s*([0-9.]+)px/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $tooSmall = array_values(array_filter($matches[1], fn (string $size) => (float) $size < 12));
        $this->assertSame([], $tooSmall, 'Found type below 12px: '.implode(', ', $tooSmall));
    }

    public function test_video_play_buttons_show_no_caption(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(".portfolio-play small {\n  display: none;\n}", $html);

        // The buttons stay reachable without the visible caption.
        $this->assertStringContainsString('aria-label="Putar video kemitraan DMC Pro dan PT Garam"', $html);

        // And the hidden caption must not be listed as something to edit.
        $captions = collect(app(TemplateContentService::class)->fields())
            ->filter(fn (array $field) => in_array($field['default'], ['Putar', 'Putar Video'], true));

        $this->assertCount(0, $captions, 'Hidden play captions are still offered as fields.');
    }

    public function test_product_panel_photo_and_share_are_editable_and_reach_the_page(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $fields = collect(app(TemplateContentService::class)->fields());

        // The markup-level image field for this panel was a no-op: the template's
        // renderBusiness() reassigns the src on every render. It must not be offered.
        $this->assertFalse(
            $fields->contains(fn (array $field) => $field['group'] === 'Produk' && $field['type'] === 'image'),
            'The dead product image field is still listed.',
        );
        $this->assertTrue($fields->contains(fn (array $field) => $field['key'] === 'dynamic.business.salt.image'));
        $this->assertTrue($fields->contains(fn (array $field) => $field['key'] === 'dynamic.business.chemical.image'));

        $this->put('/cms/content', [
            'media' => [
                'dynamic__business__salt__image' => UploadedFile::fake()->image('garam.jpg', 1200, 1500),
            ],
            'contents' => [
                'dynamic__business__salt__share' => ['id' => '65%'],
            ],
        ])->assertSessionHasNoErrors();

        $photo = SiteContent::query()->where('content_key', 'dynamic.business.salt.image')->value('value_id');
        $this->assertNotNull($photo);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $photo));

        // The values have to arrive in the payload renderBusiness() reads from,
        // otherwise the script would paint the built-in photo straight over them.
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString($photo, $html);
        $this->assertStringContainsString('"share":{"id":"65%"', $html);
        $this->assertStringContainsString('paintActiveBusiness', $html);
        $this->assertStringContainsString('base.share ||', $html);
    }

    public function test_footer_company_line_survives_a_logo_upload(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->put('/cms/branding', [
            'frontend_logo' => UploadedFile::fake()->image('logo.png', 400, 140),
        ])->assertSessionHasNoErrors();

        $logo = SiteSetting::query()->where('setting_key', 'frontend_logo')->value('value');
        $this->assertNotNull($logo);

        $html = $this->get('/')->assertOk()->getContent();
        preg_match('/<div class="footer-brand">.*?<\/div>/s', $html, $footer);

        $this->assertNotEmpty($footer, 'The footer brand block is missing.');
        $this->assertStringContainsString($logo, $footer[0]);
        $this->assertStringContainsString('PT. Dynamika Multi Compro', $footer[0]);
    }

    public function test_preview_annotations_are_limited_to_signed_in_editors(): void
    {
        $this->seed();

        $guest = $this->get('/?cms_preview=1')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-cms-key', $guest);
        $this->assertStringNotContainsString('__dmcCmsSetText', $guest);

        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $plain = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-cms-key', $plain);

        $preview = $this->get('/?cms_preview=1')->assertOk()->getContent();
        $this->assertStringContainsString('__dmcCmsSetText', $preview);
        $this->assertStringContainsString('__dmcCmsSetDynamic', $preview);

        // Every field the CMS lists for the markup must be reachable from a click.
        preg_match_all('/data-cms-key="([^"]+)"/', $preview, $matches);
        $annotated = [];
        foreach ($matches[1] as $group) {
            foreach (preg_split('/\s+/', $group) as $key) {
                $annotated[$key] = true;
            }
        }

        $expected = collect(app(TemplateContentService::class)->fields())
            ->reject(fn (array $field) => str_starts_with($field['key'], 'dynamic.'))
            ->pluck('key');

        $missing = $expected->reject(fn (string $key) => isset($annotated[$key]))->values();
        $this->assertSame([], $missing->all(), 'Unreachable fields: '.$missing->take(8)->implode(', '));
    }

    public function test_preview_visits_are_not_counted_as_traffic(): void
    {
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->get('/?cms_preview=1')->assertOk();
        $this->assertSame(0, PageView::query()->count());

        $this->get('/')->assertOk();
        $this->assertSame(1, PageView::query()->count());
    }

    public function test_content_editor_ships_a_two_way_live_preview(): void
    {
        $this->seed();
        $this->actingAs(User::query()->where('role', 'developer')->firstOrFail());

        $this->get('/cms/content/hero')->assertOk()
            ->assertSee('Pratinjau langsung')
            ->assertSee('Klik teks atau foto di pratinjau untuk membuka kolomnya.')
            ->assertSee('cms_preview=1', false)
            ->assertSee('data-preview-frame', false)
            ->assertSee('data-field-key="dynamic.hero.background"', false)
            ->assertSee('dmc-cms-editor', false)
            ->assertSee('Sembunyikan pratinjau')
            ->assertViewHas('fieldDirectory', fn (array $directory) => ($directory['text.0238']['slug'] ?? null) === 'footer');
    }
}
