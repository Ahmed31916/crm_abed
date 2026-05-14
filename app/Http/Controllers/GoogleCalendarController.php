<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use App\Models\Setting;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function getEvents(Request $request)
    {
        try {
            $events = $this->calendarService->getEvents(
                auth()->id(),
                $request->get('maxResults', 50)
            );

            return response()->json([
                'success' => true,
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'events' => []
            ]);
        }
    }

    public function syncEvents(Request $request)
    {
        try {
            $isEnabled = $this->calendarService->isEnabled(createdBy());
            $isAuthorized = $this->calendarService->isAuthorized(createdBy());

            \Log::info('Google Calendar sync check', [
                'user_id' => createdBy(),
                'isEnabled' => $isEnabled,
                'isAuthorized' => $isAuthorized
            ]);

            if (!$isEnabled || !$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar not configured. Please configure Google Calendar JSON credentials in settings.',
                    'needsConfig' => true
                ]);
            }

            $events = $this->calendarService->getEvents(auth()->id(), 100);

            $events = collect($events)->filter(function ($event) {
                $cleanedId = str_replace('google_', '', $event['id']);
                return \App\Models\Meeting::where('google_calendar_event_id', $cleanedId)->exists() ||
                       \App\Models\Call::where('google_calendar_event_id', $cleanedId)->exists();
            })->values()->all();

            return response()->json([
                'success' => true,
                'message' => 'Calendar events synchronized successfully',
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync calendar events: ' . $e->getMessage()
            ]);
        }
    }

    public function checkStatus()
    {
        try {
            $isEnabled = $this->calendarService->isEnabled(createdBy());
            $isAuthorized = $this->calendarService->isAuthorized(createdBy());

            return response()->json([
                'success' => true,
                'enabled' => $isEnabled,
                'authorized' => $isAuthorized
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
