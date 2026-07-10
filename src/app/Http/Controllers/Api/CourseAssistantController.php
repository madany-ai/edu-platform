<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseAssistantController extends Controller
{
    public function index(Course $course): JsonResponse
    {
        $this->authorizeAccess($course);

        return response()->json([
            'data' => $course->assistants()->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
        ]);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorizeAccess($course);

        if (! $request->user()->hasRole('instructor')) {
            return response()->json(['message' => 'غير مصرح لك بإضافة مساعدين.'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $assistant = User::findOrFail($validated['user_id']);

        if ($assistant->id === $course->instructor_id) {
            return response()->json(['message' => 'لا يمكن إضافة المدرس نفسه كمساعد.'], 422);
        }

        if (! $assistant->hasRole('assistant')) {
            $assistant->assignRole('assistant');
        }

        $exists = $course->assistants()->where('user_id', $assistant->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'هذا المستخدم مضاف بالفعل.'], 422);
        }

        $course->assistants()->attach($assistant->id);

        return response()->json([
            'message' => 'تم إضافة المساعد بنجاح.',
            'data' => [
                'id' => $assistant->id,
                'name' => $assistant->name,
                'email' => $assistant->email,
            ],
        ], 201);
    }

    public function destroy(Course $course, User $assistant): JsonResponse
    {
        $this->authorizeAccess($course);

        if (! request()->user()->hasRole('instructor')) {
            return response()->json(['message' => 'غير مصرح لك بإزالة مساعدين.'], 403);
        }

        $course->assistants()->detach($assistant->id);

        return response()->json(['message' => 'تم إزالة المساعد بنجاح.']);
    }

    private function authorizeAccess(Course $course): void
    {
        $user = request()->user();

        $isInstructor = $user->id === $course->instructor_id;
        $isAssistant = $course->assistants()->where('user_id', $user->id)->exists();

        if (! $isInstructor && ! $isAssistant) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الدورة.');
        }
    }
}
