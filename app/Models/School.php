<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'subdomain', 'settings', 'logo_path'];

    protected $casts = [
        'settings' => 'array',
    ];

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
}
