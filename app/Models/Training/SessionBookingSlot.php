<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SessionBookingSlot extends Model
{
    use HasFactory;

    public const TYPE_EXAM = 'exam';

    public const TYPE_MENTOR_SESSION = 'mentor_session';

    public const TYPE_OPEN_SLOT = 'open_slot';

    protected $guarded = [];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'picked_up_at' => 'datetime',
    ];

    public static function typeOptions(): array
    {
        return [
            self::TYPE_EXAM => 'Exam',
            self::TYPE_MENTOR_SESSION => 'Mentor Session',
            self::TYPE_OPEN_SLOT => 'Open Slot',
        ];
    }

    public function isExam(): bool
    {
        return $this->session_type === self::TYPE_EXAM;
    }

    public function isMentorSession(): bool
    {
        return $this->session_type === self::TYPE_MENTOR_SESSION;
    }

    public function isOpenSlot(): bool
    {
        return $this->session_type === self::TYPE_OPEN_SLOT;
    }

    public function isPickedUp(): bool
    {
        return $this->picked_up_at !== null;
    }

    public function canBePickedUpBy(string $role): bool
    {
        if ($this->isExam()) {
            return $role === 'examiner';
        }

        if ($this->isMentorSession()) {
            return $role === 'mentor';
        }

        if ($this->isOpenSlot()) {
            return in_array($role, ['mentor', 'examiner'], true);
        }

        return false;
    }

    public function roleRestrictionLabel(): string
    {
        return match (true) {
            $this->isExam() => 'Examiner only',
            $this->isMentorSession() => 'Mentor only',
            $this->isOpenSlot() => 'Mentor or Examiner',
            default => 'Unknown',
        };
    }

    public function requiresHiddenBookedByDisplay(): bool
    {
        return $this->isExam() || $this->isMentorSession();
    }

    public function publicBookedByLabel(): string
    {
        if (! $this->isPickedUp()) {
            return 'Open';
        }

        if ($this->requiresHiddenBookedByDisplay()) {
            return 'HIDDEN';
        }

        return $this->formatPublicBookerName();
    }

    public function formatPublicBookerName(): string
    {
        $name = trim((string) $this->picked_up_by_name);

        if ($name === '') {
            return 'Unknown';
        }

        $nameParts = preg_split('/\s+/', $name) ?: [];
        $firstName = $nameParts[0] ?? $name;
        $surnameInitial = isset($nameParts[1]) ? Str::upper(Str::substr($nameParts[1], 0, 1)) : null;

        $displayName = $surnameInitial ? sprintf('%s %s', $firstName, $surnameInitial) : $firstName;

        if (! $this->picked_up_by_cid) {
            return $displayName;
        }

        return sprintf('%s (%s)', $displayName, $this->picked_up_by_cid);
    }

}
