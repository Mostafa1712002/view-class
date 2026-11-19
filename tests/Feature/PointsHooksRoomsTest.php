<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\DiscussionComment;
use App\Models\DiscussionRoom;
use App\Models\DiscussionTopic;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\VirtualClass;
use App\Modules\Gamification\Hooks\DiscussionPointHooks;
use App\Modules\Gamification\Hooks\VirtualClassPointHooks;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use App\Modules\VirtualClasses\Models\VirtualClassAttendee;
use Tests\TestCase;

class PointsHooksRoomsTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*',
            '2026_06_12_23500*', '2026_06_13_000100_*', '2026_06_15_500000_extend_virtual*', '2026_06_30_0001*', '2026_11_19_371*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
        // virtual_classes targeting migration is MySQL-only (JSON_ARRAY backfill): minimal columns instead.
        \Illuminate\Support\Facades\Schema::table('virtual_classes', function ($t) {
            $t->string('target_type', 20)->default('all');
            $t->json('grade_levels')->nullable();
            $t->json('class_ids')->nullable();
        });
        \Illuminate\Support\Facades\Schema::create('virtual_class_targets', function ($t) {
            $t->id();
            $t->unsignedBigInteger('virtual_class_id');
            $t->string('kind');
            $t->unsignedBigInteger('target_id');
            $t->timestamps();
        });
    }

    private function balance(User $u): int
    {
        return app(PointLedgerRepository::class)->balance($u->id);
    }

    private function fixture(): array
    {
        $school = School::factory()->create();
        foreach (['teacher.discussion_participation', 'teacher.live_rooms_attendance'] as $rule) {
            PointRuleSetting::create(['school_id' => 0, 'rule_key' => $rule, 'points' => 10, 'is_enabled' => true]);
        }
        $class = ClassRoom::factory()->create();
        $other = ClassRoom::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->roles()->attach(Role::where('slug', 'teacher')->first());
        $students = [];
        foreach ([1, 2, 3] as $i) {
            $s = User::factory()->create(['school_id' => $school->id, 'class_room_id' => $i < 3 ? $class->id : $other->id, 'email' => "s$i@t.com"]);
            $s->roles()->attach(Role::where('slug', 'student')->first());
            $students[] = $s;
        }

        return [$school, $class, $teacher, $students];
    }

    public function test_discussion_participation_targeting_and_delete(): void
    {
        [$school, $class, $teacher, [$s1, $s2, $s3]] = $this->fixture();
        $room = DiscussionRoom::create(['school_id' => $school->id, 'title' => 'R', 'created_by' => $teacher->id,
            'target_type' => 'students', 'class_ids' => [$class->id]]);
        $topic = DiscussionTopic::create(['room_id' => $room->id, 'school_id' => $school->id, 'created_by' => $teacher->id, 'title' => 'T', 'body' => 'b']);
        $hooks = app(DiscussionPointHooks::class);

        DiscussionComment::create(['topic_id' => $topic->id, 'school_id' => $school->id, 'user_id' => $s1->id, 'body' => 'x']);
        DiscussionComment::create(['topic_id' => $topic->id, 'school_id' => $school->id, 'user_id' => $s1->id, 'body' => 'y']);   // distinct
        DiscussionComment::create(['topic_id' => $topic->id, 'school_id' => $school->id, 'user_id' => $s3->id, 'body' => 'z']);   // not targeted
        $hooks->discussionTopicChanged($topic->id);
        $hooks->discussionTopicChanged($topic->id);   // idempotent
        $this->assertSame(5, $this->balance($teacher)); // 1 of 2 targeted

        DiscussionComment::create(['topic_id' => $topic->id, 'school_id' => $school->id, 'user_id' => $s2->id, 'body' => 'w']);
        $hooks->discussionRoomChanged($room->id);
        $this->assertSame(10, $this->balance($teacher));

        $room->delete();
        $hooks->discussionRoomDeleted($room->id);
        $this->assertSame(0, $this->balance($teacher));
    }

    public function test_live_rooms_attendance_counts_present_and_late_only(): void
    {
        [$school, $class, $teacher, [$s1, $s2, $s3]] = $this->fixture();
        $vc = VirtualClass::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'title' => 'V', 'class_id' => $class->id,
            'scheduled_at' => now(), 'created_by' => $teacher->id, 'target_type' => 'all']);
        $log = fn ($s, $status) => VirtualClassAttendee::create(['virtual_class_id' => $vc->id, 'student_id' => $s->id, 'school_id' => $school->id, 'attendance_status' => $status]);
        $log($s1, 'present');
        $log($s2, 'partial');
        $hooks = app(VirtualClassPointHooks::class);

        $hooks->virtualClassRecalculated($vc);
        $this->assertSame(3, $this->balance($teacher)); // 1 of 3 school students -> 3.33 -> 3

        VirtualClassAttendee::where('student_id', $s2->id)->update(['attendance_status' => 'late']);
        $hooks->virtualClassRecalculated($vc);
        $this->assertSame(7, $this->balance($teacher)); // 2 of 3 -> 6.67 -> 7
    }

    public function test_changing_the_audience_resyncs_room_and_live_session(): void
    {
        [$school, $class, $teacher, [$s1, $s2, $s3]] = $this->fixture();
        $room = DiscussionRoom::create(['school_id' => $school->id, 'title' => 'R', 'created_by' => $teacher->id,
            'target_type' => 'students', 'class_ids' => [$class->id]]);
        $topic = DiscussionTopic::create(['room_id' => $room->id, 'school_id' => $school->id, 'created_by' => $teacher->id, 'title' => 'T', 'body' => 'b']);
        DiscussionComment::create(['topic_id' => $topic->id, 'school_id' => $school->id, 'user_id' => $s1->id, 'body' => 'x']);
        $hooks = app(DiscussionPointHooks::class);
        $hooks->discussionRoomChanged($room->id);
        $this->assertSame(5, $this->balance($teacher));   // 1 of 2

        $room->update(['class_ids' => null, 'target_type' => 'all']);   // widened to 3 students
        $hooks->discussionRoomChanged($room->id);
        $this->assertSame(3, $this->balance($teacher));   // 1 of 3
    }
}
