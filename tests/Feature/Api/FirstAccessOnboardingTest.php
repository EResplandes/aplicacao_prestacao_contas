<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FirstAccessOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_login_returns_pending_onboarding_until_payout_data_is_completed(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $requester->email,
            'password' => 'password',
            'device_name' => 'flutter-web',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('data.user.requires_onboarding', true);

        Storage::fake('public');
        Sanctum::actingAs($requester);

        $onboardingResponse = $this->post(
            '/api/v1/profile/onboarding',
            [
                'payment_method' => 'pix',
                'account_holder_name' => 'Solicitante',
                'account_holder_document' => '12345678901',
                'pix_key_type' => 'email',
                'pix_key' => 'solicitante@empresa.com',
                'profile_photo' => UploadedFile::fake()->image('selfie.jpg'),
            ],
            ['Accept' => 'application/json'],
        );

        $onboardingResponse
            ->assertOk()
            ->assertJsonPath('data.requires_onboarding', false)
            ->assertJsonPath('data.payout_account.payment_method', 'pix');

        $this->assertDatabaseHas('user_payout_accounts', [
            'user_id' => $requester->id,
            'payment_method' => 'pix',
            'pix_key_type' => 'email',
        ]);

        Storage::disk('public')->assertExists(
            (string) $requester->fresh()->payoutAccount?->profile_photo_path,
        );

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.requires_onboarding', false)
            ->assertJsonPath('data.payout_account.account_holder_name', 'Solicitante');
    }
}
