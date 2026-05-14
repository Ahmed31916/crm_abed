<?php

namespace App\Services;

use App\Models\Setting;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;

class GoogleCalendarService
{
    private $client;
    private $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->service = new Google_Service_Calendar($this->client);
    }

    public function isEnabled($userId)
    {
        $enabled = getSetting('googleCalendarEnabled', null, $userId) === '1';
        \Log::info('Google Calendar enabled check', ['user_id' => $userId, 'enabled' => $enabled]);
        return $enabled;
    }

    private function setupClient($userId)
    {
        $settings = Setting::where('user_id', $userId)
            ->whereIn('key', ['googleCalendarJsonPath', 'googleCalendarId'])
            ->pluck('value', 'key');

        $jsonPath = $settings['googleCalendarJsonPath'] ?? null;

        if (!$jsonPath) {
            throw new \Exception('Google Calendar JSON credentials not configured');
        }

        $paths = [
            $jsonPath,
            storage_path('app/public/' . $jsonPath),
            storage_path('app/' . $jsonPath),
            storage_path($jsonPath),
            base_path($jsonPath),
            public_path('storage/' . $jsonPath),
        ];

        $validPath = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $validPath = $path;
                break;
            }
        }

        if (!$validPath) {
            throw new \Exception('Google Calendar JSON file not found at: ' . $jsonPath);
        }

        $this->client->setAuthConfig($validPath);
        $this->client->setScopes(Google_Service_Calendar::CALENDAR);
        $this->client->useApplicationDefaultCredentials();
    }

    public function createEvent($item, $userId, $type = 'meeting')
    {
        if (!$this->isEnabled($userId)) {
            \Log::info('Google Calendar not enabled', ['user_id' => $userId]);
            return null;
        }

        try {
            \Log::info('Creating Google Calendar event', ['user_id' => $userId, 'type' => $type]);
            $this->setupClient($userId);

            $summary = $item->title ?? 'Event';
            $description = $item->description ?? '';

            $event = new Google_Service_Calendar_Event([
                'summary' => $summary,
                'description' => $description,
                'location' => $item->location ?? '',
                'extendedProperties' => [
                    'private' => [
                        'app_type' => $type,
                        'app_id' => $item->id,
                        'app_user_id' => $userId
                    ]
                ]
            ]);

            $userTimezone = getSetting('defaultTimezone', 'Asia/Kolkata', $userId);

            if ($type === 'meeting' && $item->start_date) {
                $startDate = $item->start_date instanceof \Carbon\Carbon ? $item->start_date->format('Y-m-d') : $item->start_date;
                $endDate = $item->end_date instanceof \Carbon\Carbon ? $item->end_date->format('Y-m-d') : $item->end_date;
                $startTimeStr = $item->start_time instanceof \Carbon\Carbon ? $item->start_time->format('H:i:s') : $item->start_time;
                $endTimeStr = $item->end_time instanceof \Carbon\Carbon ? $item->end_time->format('H:i:s') : $item->end_time;

                $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startDate . ' ' . $startTimeStr, $userTimezone);
                $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $endDate . ' ' . $endTimeStr, $userTimezone);
            } elseif ($type === 'call' && $item->start_date) {
                $startDate = $item->start_date instanceof \Carbon\Carbon ? $item->start_date->format('Y-m-d') : $item->start_date;
                $endDate = $item->end_date instanceof \Carbon\Carbon ? $item->end_date->format('Y-m-d') : $item->end_date;
                $startTimeStr = $item->start_time instanceof \Carbon\Carbon ? $item->start_time->format('H:i:s') : $item->start_time;
                $endTimeStr = $item->end_time instanceof \Carbon\Carbon ? $item->end_time->format('H:i:s') : $item->end_time;

                $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startDate . ' ' . $startTimeStr, $userTimezone);
                $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $endDate . ' ' . $endTimeStr, $userTimezone);
            } elseif ($type === 'task' && $item->due_date) {
                $dueDate = $item->due_date instanceof \Carbon\Carbon ? $item->due_date->format('Y-m-d') : $item->due_date;
                $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dueDate . ' 09:00:00', $userTimezone);
                $endTime = $startTime->copy()->addHour();
            } else {
                return null;
            }

            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startTime->format('c'));
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endTime->format('c'));
            $event->setEnd($end);

            $calendarId = Setting::where('user_id', $userId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';

            $calendarEvent = $this->service->events->insert($calendarId, $event);
            return $calendarEvent->getId();
        } catch (\Exception $e) {
            \Log::error('Google Calendar event creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'type' => $type,
                'item_id' => $item->id ?? 'unknown'
            ]);
            return null;
        }
    }

    public function updateEvent($eventId, $item, $userId, $type = 'meeting')
    {
        if (!$this->isEnabled($userId) || !$eventId) {
            return false;
        }

        try {
            $this->setupClient($userId);

            $calendarId = Setting::where('user_id', $userId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';

            $event = $this->service->events->get($calendarId, $eventId);

            $event->setSummary($item->title ?? 'Event');
            $event->setDescription($item->description ?? '');
            $event->setLocation($item->location ?? '');

            $userTimezone = getSetting('defaultTimezone', 'Asia/Kolkata', $userId);

            if ($type === 'meeting' && $item->start_date) {
                $startDate = $item->start_date instanceof \Carbon\Carbon ? $item->start_date->format('Y-m-d') : $item->start_date;
                $endDate = $item->end_date instanceof \Carbon\Carbon ? $item->end_date->format('Y-m-d') : $item->end_date;
                $startTimeStr = $item->start_time instanceof \Carbon\Carbon ? $item->start_time->format('H:i:s') : $item->start_time;
                $endTimeStr = $item->end_time instanceof \Carbon\Carbon ? $item->end_time->format('H:i:s') : $item->end_time;

                $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startDate . ' ' . $startTimeStr, $userTimezone);
                $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $endDate . ' ' . $endTimeStr, $userTimezone);
            } elseif ($type === 'call' && $item->start_date) {
                $startDate = $item->start_date instanceof \Carbon\Carbon ? $item->start_date->format('Y-m-d') : $item->start_date;
                $endDate = $item->end_date instanceof \Carbon\Carbon ? $item->end_date->format('Y-m-d') : $item->end_date;
                $startTimeStr = $item->start_time instanceof \Carbon\Carbon ? $item->start_time->format('H:i:s') : $item->start_time;
                $endTimeStr = $item->end_time instanceof \Carbon\Carbon ? $item->end_time->format('H:i:s') : $item->end_time;

                $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startDate . ' ' . $startTimeStr, $userTimezone);
                $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $endDate . ' ' . $endTimeStr, $userTimezone);
            } elseif ($type === 'task' && $item->due_date) {
                $dueDate = $item->due_date instanceof \Carbon\Carbon ? $item->due_date->format('Y-m-d') : $item->due_date;
                $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dueDate . ' 09:00:00', $userTimezone);
                $endTime = $startTime->copy()->addHour();
            } else {
                return false;
            }

            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startTime->format('c'));
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endTime->format('c'));
            $event->setEnd($end);

            $this->service->events->update($calendarId, $eventId, $event);
            return true;
        } catch (\Exception $e) {
            \Log::error('Google Calendar event update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteEvent($eventId, $userId)
    {
        if (!$this->isEnabled($userId) || !$eventId) {
            return false;
        }

        try {
            $this->setupClient($userId);
            $calendarId = Setting::where('user_id', $userId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';

            $this->service->events->delete($calendarId, $eventId);
            return true;
        } catch (\Exception $e) {
            \Log::error('Google Calendar event deletion failed: ' . $e->getMessage());
            return false;
        }
    }


    public function getEvents($userId, $maxResults = 100, $timeMin = null, $timeMax = null)
    {
        $settingsUserId = createdBy();

        if (!$this->isEnabled($settingsUserId)) {
            return [];
        }

        try {
            $this->setupClient($settingsUserId);

            $calendarId = Setting::where('user_id', $settingsUserId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';

            $optParams = [
                'maxResults' => $maxResults,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $timeMin ?: date('c', strtotime('-1 month')),
            ];

            if ($timeMax) {
                $optParams['timeMax'] = $timeMax;
            }

            $results = $this->service->events->listEvents($calendarId, $optParams);
            $events = $results->getItems();

            return array_map(function($event) {
                $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
                $end = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();

                $extendedProps = $event->getExtendedProperties();
                $type = 'event';

                if ($extendedProps && $extendedProps->getPrivate()) {
                    $privateProps = $extendedProps->getPrivate();
                    $type = $privateProps['app_type'] ?? 'event';
                }

                return [
                    'id' => 'google_' . $event->getId(),
                    'title' => $event->getSummary() ?: 'Untitled Event',
                    'start' => $start,
                    'end' => $end,
                    'color' => $type === 'meeting' ? '#3b82f6' : ($type === 'call' ? '#10b77f' : '#f59e0b'),
                    'meeting_id' => $type === 'meeting' ? ($privateProps['app_id'] ?? null) : null,
                    'call_id' => $type === 'call' ? ($privateProps['app_id'] ?? null) : null,
                    'extendedProps' => [
                        'type' => $type,
                        'description' => $event->getDescription() ?: '',
                        'location' => $event->getLocation() ?: '',
                        'source' => 'google',
                    ]
                ];
            }, $events);
        } catch (\Exception $e) {
            \Log::error('Google Calendar events fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    public function isAuthorized($userId)
    {
        $jsonPath = Setting::where('user_id', $userId)
            ->where('key', 'googleCalendarJsonPath')
            ->first();

        if (!$jsonPath || empty($jsonPath->value)) {
            return false;
        }

        $paths = [
            $jsonPath->value,
            storage_path('app/public/' . $jsonPath->value),
            storage_path('app/' . $jsonPath->value),
            storage_path($jsonPath->value),
            base_path($jsonPath->value),
            public_path('storage/' . $jsonPath->value),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }

        return false;
    }
}
