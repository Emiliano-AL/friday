<?php

namespace App\Models;

use App\Enums\ProjectInvitationStatus;
use App\Enums\ProjectStatus;
use App\Mail\ProjectInvitationMail;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property int $id
 * @property int $project_id
 * @property int $invited_by_id
 * @property string $email
 * @property string $token_hash
 * @property ProjectInvitationStatus $status
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $last_sent_at
 * @property CarbonImmutable|null $responded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 * @property-read User $invitedBy
 */
#[Fillable(['project_id', 'invited_by_id', 'email', 'token_hash', 'status', 'expires_at', 'last_sent_at', 'responded_at'])]
class ProjectInvitation extends Model
{
    /** @use HasFactory<ProjectInvitationFactory> */
    use HasFactory;

    public const int VALID_DAYS = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectInvitationStatus::class,
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    /**
     * Limit the query to invitations still awaiting an answer (expired ones included).
     *
     * @param  Builder<ProjectInvitation>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ProjectInvitationStatus::Pending);
    }

    public function isExpired(): bool
    {
        return $this->status === ProjectInvitationStatus::Pending && $this->expires_at->isPast();
    }

    /**
     * Whether the invitation can still be accepted or declined.
     */
    public function isUsable(): bool
    {
        return $this->status === ProjectInvitationStatus::Pending
            && ! $this->isExpired()
            && $this->project->status === ProjectStatus::Active;
    }

    public function matches(User $user): bool
    {
        return Str::lower($user->email) === $this->email;
    }

    public static function findByToken(string $token): ?self
    {
        return self::query()->where('token_hash', hash('sha256', $token))->first();
    }

    /**
     * Create the pending invitation for the email, or refresh the existing one with a new token.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(Project $project, string $email, User $inviter): array
    {
        $token = Str::random(64);

        $invitation = self::query()->firstOrNew([
            'project_id' => $project->id,
            'email' => Str::lower($email),
            'status' => ProjectInvitationStatus::Pending,
        ]);

        $invitation->fill([
            'invited_by_id' => $inviter->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(self::VALID_DAYS),
            'last_sent_at' => now(),
        ])->save();

        return [$invitation, $token];
    }

    /**
     * Queue the invitation mail; a failed dispatch is reported and leaves the invitation pending.
     */
    public function send(string $token): bool
    {
        try {
            Mail::to($this->email)->queue(new ProjectInvitationMail($this, $token));
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }
}
