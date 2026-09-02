<?php

namespace Tests\Feature;

use App\Support\Branding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoginBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_uploaded_logo(): void
    {
        Storage::fake('public');

        $logoPath = UploadedFile::fake()->image('logo.png')->store('branding', 'public');
        Branding::setLogoPath($logoPath);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($logoPath), false);
    }
}
