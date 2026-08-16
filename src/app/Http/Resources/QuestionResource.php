<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author_name' => $this->author_name,
            'student' => $this->student ? [
                'id' => $this->student->id,
                'name' => $this->student->user?->name ?? 'محذوف',
            ] : null,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'lecture' => $this->lecture ? [
                'id' => $this->lecture->id,
                'title' => $this->lecture->title,
                'course' => $this->lecture->section?->course
                    ? [
                        'id' => $this->lecture->section->course->id,
                        'title' => $this->lecture->section->course->title,
                    ] : null,
            ] : null,
            'replies_count' => $this->whenCounted('replies'),
            'replies' => QuestionReplyResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
