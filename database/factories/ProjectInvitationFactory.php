<?php

namespace Database\Factories;

use App\Enums\ProjectInvitationStatus;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectInvitation>
 */
class ProjectInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'invited_by_id' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token_hash' => hash('sha256', Str::random(64)),
            'status' => ProjectInvitationStatus::Pending,
            'expires_at' => now()->addDays(ProjectInvitation::VALID_DAYS),
            'last_sent_at' => now(),
        ];
    }

    /**
     * Use a known plain token so tests can open the invitation link.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn () => ['token_hash' => hash('sha256', $token)]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => ProjectInvitationStatus::Accepted, 'responded_at' => now()]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => ProjectInvitationStatus::Declined, 'responded_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => ProjectInvitationStatus::Cancelled, 'responded_at' => now()]);
    }
}
