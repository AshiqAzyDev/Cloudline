<?php

namespace Tests\Feature;

use App\Livewire\Settings\Index;
use App\Support\Branding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_logo_and_favicon(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'branding')
            ->set('logoUpload', UploadedFile::fake()->image('logo.png'))
            ->assertHasNoErrors()
            ->set('faviconUpload', UploadedFile::fake()->create('favicon.png', 10, 'image/png'))
            ->assertHasNoErrors();

        $this->assertNotNull(Branding::logoPath());
        $this->assertNotNull(Branding::faviconPath());
        Storage::disk('public')->assertExists(Branding::logoPath());
        Storage::disk('public')->assertExists(Branding::faviconPath());
    }

    public function test_admin_can_upload_svg_logo(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'branding')
            ->set('logoUpload', UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'))
            ->assertHasNoErrors();

        $this->assertNotNull(Branding::logoPath());
        Storage::disk('public')->assertExists(Branding::logoPath());
    }

    public function test_save_branding_without_files_shows_error(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'branding')
            ->call('saveBranding')
            ->assertHasErrors(['logoUpload']);
    }

    public function test_admin_can_remove_uploaded_branding(): void
    {
        Storage::fake('public');

        $logoPath = UploadedFile::fake()->image('logo.png')->store('branding', 'public');
        Branding::setLogoPath($logoPath);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'branding')
            ->call('removeLogo')
            ->assertHasNoErrors();

        $this->assertNull(Branding::logoPath());
        Storage::disk('public')->assertMissing($logoPath);
    }
}
