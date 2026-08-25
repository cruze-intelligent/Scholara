<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class School extends Model
{
    use HasFactory;

    // 'pending_review' (self-registered, awaiting super-admin approval), 'trial' (approved,
    // inside its free trial_ends_at window), 'active' (manually activated or covered by a paid
    // SchoolSubscription — see isAccessible()), 'suspended', 'rejected'.
    public const STATUSES = ['pending_review', 'trial', 'active', 'suspended', 'rejected'];

    protected $fillable = [
        'name', 'address', 'subdomain', 'settings', 'logo_path',
        'registration_number', 'moe_registration_number', 'status', 'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SchoolSubscription::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    /**
     * @return array<int, string>
     */
    public function levels(): array
    {
        return $this->settings['levels'] ?? [];
    }

    /**
     * Whether this school offers the given curriculum level (nursery/primary/lower_secondary/
     * upper_secondary) — gates level-specific modules (e.g. Nursery daily logs) out of the nav
     * for schools that don't run that level. An unconfigured school (no admin has set this yet)
     * offers every level, so nothing disappears before an admin has had a chance to configure it.
     */
    public function offersLevel(string $level): bool
    {
        $levels = $this->levels();

        return $levels === [] || in_array($level, $levels, true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * A base64 data: URI rather than the storage URL above — dompdf renders each generated
     * document (report cards, payslips, receipts) outside of a real HTTP request, so it can't
     * reliably fetch the logo over the network; embedding the bytes directly always works.
     */
    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($this->logo_path);
        $contents = base64_encode(Storage::disk('public')->get($this->logo_path));

        return "data:{$mime};base64,{$contents}";
    }

    /**
     * Whether anyone at this school (other than a super_admin, who isn't tied to a school at
     * all) should be let past EnsureSchoolApproved into the real app right now. 'active' is a
     * manual override; a 'trial' school is fine until its trial ends, after which — and for a
     * status of 'active' with no manual flag either — it needs a SchoolSubscription that's
     * actually paid and covers today. pending_review/suspended/rejected are always blocked.
     */
    public function isAccessible(): bool
    {
        if (in_array($this->status, ['pending_review', 'suspended', 'rejected'], true)) {
            return false;
        }

        if ($this->status === 'active') {
            return true;
        }

        // status === 'trial': fine inside the trial window; past it, needs a paid subscription
        // covering today (status itself is never auto-flipped to 'active' on payment — this is
        // computed live instead of relying on a scheduled job to run).
        if ($this->trial_ends_at?->isFuture()) {
            return true;
        }

        return $this->subscriptions->contains(fn (SchoolSubscription $sub) => $sub->coversToday());
    }

    /**
     * A short, unique reference for this school — assigned once, at registration. Not
     * sequential/guessable on purpose (this is shown back to the registrant as "your school's
     * number," not an internal primary key).
     */
    public static function generateRegistrationNumber(): string
    {
        do {
            $candidate = 'SCH-'.strtoupper(Str::random(6));
        } while (self::where('registration_number', $candidate)->exists());

        return $candidate;
    }
}
