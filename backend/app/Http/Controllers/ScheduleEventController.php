<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEvent;
use Illuminate\Http\Request;

class ScheduleEventController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        
        $events = ScheduleEvent::where('user_id', $user->id)
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => (string) $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'category' => $event->category,
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'color' => $event->color,
                    'allDay' => $event->all_day
                ];
            });

        return response()->json(['events' => $events], 200);
    }


    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:study,exam,meeting,personal,other',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'color' => 'required|string',
            'allDay' => 'boolean'
        ]);

        $event = ScheduleEvent::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'start_time' => $data['start'],
            'end_time' => $data['end'],
            'color' => $data['color'],
            'all_day' => $data['allDay'] ?? false
        ]);

        return response()->json([
            'message' => 'Event created successfully',
            'event' => [
                'id' => (string) $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'category' => $event->category,
                'start' => $event->start_time,
                'end' => $event->end_time,
                'color' => $event->color,
                'allDay' => $event->all_day
            ]
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $event = ScheduleEvent::where('user_id', $user->id)
            ->findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:study,exam,meeting,personal,other',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'color' => 'required|string',
            'allDay' => 'boolean'
        ]);

        $event->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'start_time' => $data['start'],
            'end_time' => $data['end'],
            'color' => $data['color'],
            'all_day' => $data['allDay'] ?? false
        ]);

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => [
                'id' => (string) $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'category' => $event->category,
                'start' => $event->start_time,
                'end' => $event->end_time,
                'color' => $event->color,
                'allDay' => $event->all_day
            ]
        ], 200);
    }


    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $event = ScheduleEvent::where('user_id', $user->id)
            ->findOrFail($id);

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully'
        ], 200);
    }
}