<?php

namespace App\Modules\Announcements\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Repositories\Contracts\AnnouncementRepository;
use Illuminate\Http\Request;

class UserAnnouncementController extends Controller
{
    public function __construct(private AnnouncementRepository $announcements) {}

    /** List of live announcements targeted to the current user. */
    public function index()
    {
        $user = auth()->user();
        $announcements = $this->announcements->liveForUser($user);

        return view('announcements.index', compact('announcements'));
    }

    /** Show a single targeted announcement and record the view. */
    public function show(Request $request, int $id)
    {
        $user = auth()->user();
        $announcement = $this->announcements->findLiveForUser($id, $user);
        if (!$announcement) {
            abort(404);
        }

        $this->announcements->recordView(
            $announcement,
            $user,
            $request->ip(),
            substr((string) $request->userAgent(), 0, 500)
        );

        return view('announcements.show', compact('announcement'));
    }

    /** Persist that the user confirmed reading the announcement. */
    public function confirm(Request $request, int $id)
    {
        $user = auth()->user();
        $announcement = $this->announcements->findLiveForUser($id, $user);
        if (!$announcement) {
            abort(404);
        }

        $this->announcements->confirmRead(
            $announcement,
            $user,
            $request->ip(),
            substr((string) $request->userAgent(), 0, 500)
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'data' => ['confirmed' => true], 'message' => 'تم تأكيد القراءة.']);
        }

        return back()->with('success', 'تم تأكيد القراءة.');
    }

    /** Mark a popup as seen (so it doesn't reappear on next login). */
    public function dismiss(Request $request, int $id)
    {
        $user = auth()->user();
        $announcement = $this->announcements->findLiveForUser($id, $user);
        if (!$announcement) {
            abort(404);
        }

        $this->announcements->recordView(
            $announcement,
            $user,
            $request->ip(),
            substr((string) $request->userAgent(), 0, 500)
        );

        return response()->json(['success' => true, 'data' => ['dismissed' => true], 'message' => 'تم.']);
    }
}
