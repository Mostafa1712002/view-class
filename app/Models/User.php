<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /** Per-request memo for managedSchoolIds(); not a DB attribute. */
    protected ?array $managedSchoolIdsCache = null;

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'username',
        'email',
        'password',
        'school_id',
        'section_id',
        'class_room_id',
        'employee_id',
        'national_id',
        'phone',
        'phone_secondary',
        'whatsapp',
        'address',
        'gender',
        'birth_place',
        'nationality',
        'birth_date',
        'date_of_birth',
        'specialization',
        'qualification',
        'job_title_id',
        'hire_date',
        'plain_password_for_card',
        'avatar',
        'profile_picture',
        'is_active',
        'status',
        'language_preference',
        'notification_preferences',
        'language',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'date_of_birth' => 'date',
            'birth_date' => 'date',
            'hire_date' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    public function getRoleNameAttribute(): string
    {
        $role = $this->roles->first();
        return match ($role?->slug) {
            'super-admin' => 'مدير النظام',
            'school-admin' => 'مدير المدرسة',
            'teacher' => 'معلم',
            'student' => 'طالب',
            'parent' => 'ولي أمر',
            default => 'مستخدم',
        };
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Schools this admin account is explicitly linked to (card #307). */
    public function managedSchools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'admin_school', 'admin_id', 'school_id')->withTimestamps();
    }

    /**
     * School ids the admin may operate within: its primary school plus any
     * explicitly linked schools. Falls back to just the primary school when no
     * links exist, so pre-#307 admins behave exactly as before.
     */
    public function managedSchoolIds(): array
    {
        if (! isset($this->managedSchoolIdsCache)) {
            $ids = $this->managedSchools()->pluck('schools.id')->all();
            if ($this->school_id) {
                $ids[] = (int) $this->school_id;
            }
            $this->managedSchoolIdsCache = array_values(array_unique(array_map('intval', $ids)));
        }

        return $this->managedSchoolIdsCache;
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher', 'user_id', 'subject_id');
    }

    public function schedulePeriods(): HasMany
    {
        return $this->hasMany(SchedulePeriod::class, 'teacher_id');
    }

    public function weeklyPlans(): HasMany
    {
        return $this->hasMany(WeeklyPlan::class, 'teacher_id');
    }

    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_student', 'student_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * All class IDs this student belongs to.
     *
     * Enrollment is written in two places that are not always kept in sync:
     * the `class_student` pivot (set on student creation) and the direct
     * `users.class_room_id` column (the only one the student-edit form
     * updates, and what seeded/imported students carry). We union both so a
     * student is considered enrolled if either source links them to a class.
     *
     * @return array<int>
     */
    public function enrolledClassIds(): array
    {
        $ids = $this->enrolledClasses()->pluck('classes.id')->all();

        if ($this->class_room_id) {
            $ids[] = (int) $this->class_room_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function teacherAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'teacher_id');
    }

    public function customNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->whereNull('read_at');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'is_muted'])
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // Parent-Student relationships
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot(['relationship', 'is_primary', 'can_receive_notifications'])
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot(['relationship', 'is_primary', 'can_receive_notifications'])
            ->withTimestamps();
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function studentExams(): HasMany
    {
        return $this->hasMany(StudentExam::class, 'student_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Evaluation-module permission check (Phase D, #208/#210).
     * Super-admins always pass; everyone else needs the granular permission.
     * Used to gate evaluation actions without locking out existing admins.
     */
    public function canEval(string $permission): bool
    {
        return $this->isSuperAdmin() || $this->hasPermission($permission);
    }

    /**
     * General-purpose gated permission check with default-allow-when-unconfigured.
     *
     * Decision tree:
     *   1. super-admin → always allow
     *   2. role has permission_role entry → allow (legacy RBAC path)
     *   3. user has a job title with configured permissions:
     *       3a. job title has this permission → allow
     *       3b. job title does NOT have this permission → deny
     *   4. no job title, OR job title has NO permissions configured → allow (default)
     *
     * Rule 4 ensures existing users are never accidentally locked out when no
     * job-title permission matrix has been configured yet.
     */
    public function canDo(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Legacy role-based check (permission_role table)
        if ($this->hasPermission($permission)) {
            return true;
        }

        // Default-allow-when-unconfigured applies ONLY to read-only ".view"
        // permissions, so existing users (whose job-title matrix isn't set up
        // yet) keep seeing menus and read pages. Every write/manage action
        // (create/edit/delete/archive/approve/import/export/manage_permissions/
        // login_as_user/...) is DENIED until explicitly granted — fail closed.
        $isReadOnly = str_ends_with($permission, '.view');

        // Job-title based check (job_title_permissions table)
        $jobTitle = $this->jobTitle;
        if ($jobTitle === null) {
            return $isReadOnly;
        }

        // If the job title has no configured permission rows at all, only the
        // read-only default survives.
        $configured = $jobTitle->permissions()->count();
        if ($configured === 0) {
            return $isReadOnly;
        }

        return $jobTitle->hasPermission($permission);
    }

    /**
     * Sidebar helper: can the user view a module's main page?
     * Uses canDo() so the default-allow rule applies automatically.
     *
     * @param string $module e.g. 'question_banks', 'students', 'reports'
     */
    public function canViewModule(string $module): bool
    {
        return $this->canDo("{$module}.view");
    }

    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching($role);
    }

    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isSchoolAdmin(): bool
    {
        return $this->hasRole('school-admin');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isParent(): bool
    {
        return $this->hasRole('parent');
    }

    public function jobTitle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    // === Teachers card 54 ===
    public function teacherProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }
    // === /Teachers card 54 ===
}
