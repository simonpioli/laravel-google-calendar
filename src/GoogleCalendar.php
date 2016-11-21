<?php

namespace Simonpioli\GoogleCalendar;

use Carbon\Carbon;
use DateTime;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_FreeBusyRequest;
use Google_Service_Calendar_FreebusyResponse;

class GoogleCalendar
{
    /** @var \Google_Service_Calendar */
    protected $calendarService;

    /** @var string */
    protected $calendarId;

    public function __construct(Google_Service_Calendar $calendarService, $calendarId)
    {
        $this->calendarService = $calendarService;

        $this->calendarId = $calendarId;
    }

    public function getCalendarId(): string
    {
        return $this->calendarId;
    }

    /**
     * Gets FreeBusy Model
     * @param  Carbon|null                              $startDateTime Start Time for Query
     * @param  Carbon|null                              $endDateTime   End Time for Query
     * @param  string|null                              $calendarId    Calendar to Query
     * @return Google_Service_Calendar_FreebusyResponse                FreeBusy Response Object
     */
    public function busy(
        Carbon $startDateTime = null,
        Carbon $endDateTime = null,
        string $calendarId = null
    ): Google_Service_Calendar_FreebusyResponse
    {
        $body = new Google_Service_Calendar_FreeBusyRequest;
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();

        if (!$startDateTime) {
            $body->setTimeMin($start->toAtomString());
        } else {
            $body->setTimeMin($startDateTime->toAtomString());
        }

        if (!$endDateTime) {
            $body->setTimeMax($end->toAtomString());
        } else {
            $body->setTimeMin($endDateTime->toAtomString());
        }

        if (!$calendarId) {
            $body->setItems([['id' => $this->calendarId]]);
        } else {
            $body->setItems([['id' => $calendarId]]);
        }

        return $this->calendarService->freebusy->query($body);
    }

    /**
     * @param \Carbon\Carbon $startDateTime
     * @param \Carbon\Carbon $endDateTime
     * @param array          $queryParameters
     *
     * @link https://developers.google.com/google-apps/calendar/v3/reference/events/list
     *
     * @return array
     */
    public function listEvents(
        Carbon $startDateTime = null,
        Carbon $endDateTime = null,
        array $queryParameters = []
    ): array {
        $parameters = ['singleEvents' => true];

        if (is_null($startDateTime)) {
            $startDateTime = Carbon::now()->startOfDay();
        }

        $parameters['timeMin'] = $startDateTime->format(DateTime::RFC3339);

        if (is_null($endDateTime)) {
            $endDateTime = Carbon::now()->addYear()->endOfDay();
        }
        $parameters['timeMax'] = $endDateTime->format(DateTime::RFC3339);

        $parameters = array_merge($parameters, $queryParameters);

        return $this
            ->calendarService
            ->events
            ->listEvents($this->calendarId, $parameters)
            ->getItems();
    }

    /**
     * Get a single event.
     *
     * @param string $eventId
     *
     * @return \Google_Service_Calendar_Event
     */
    public function getEvent(string $eventId): Google_Service_Calendar_Event
    {
        return $this->calendarService->events->get($this->calendarId, $eventId);
    }

    /**
     * Insert an event.
     *
     * @param \Simonpioli\GoogleCalendar\Event|Google_Service_Calendar_Event $event
     *
     * @link https://developers.google.com/google-apps/calendar/v3/reference/events/insert
     *
     * @return \Google_Service_Calendar_Event
     */
    public function insertEvent($event): Google_Service_Calendar_Event
    {
        if ($event instanceof Event) {
            $event = $event->googleEvent;
        }

        return $this->calendarService->events->insert($this->calendarId, $event);
    }

    /**
     * @param \Simonpioli\GoogleCalendar\Event|Google_Service_Calendar_Event $event
     *
     * @return \Google_Service_Calendar_Event
     */
    public function updateEvent($event): Google_Service_Calendar_Event
    {
        if ($event instanceof Event) {
            $event = $event->googleEvent;
        }

        return $this->calendarService->events->update($this->calendarId, $event->id, $event);
    }

    /**
     * @param string|\Simonpioli\GoogleCalendar\Event $eventId
     */
    public function deleteEvent($eventId)
    {
        if ($eventId instanceof Event) {
            $eventId = $eventId->id;
        }

        $this->calendarService->events->delete($this->calendarId, $eventId);
    }

    public function getService(): Google_Service_Calendar
    {
        return $this->calendarService;
    }
}
