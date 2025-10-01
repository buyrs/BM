@component('mail::message')
# Mission Completed

Hello {{ $user->name }},

The mission for **{{ $data['property_name'] }}** has been completed.

**Mission Details:**
- Property: {{ $data['property_name'] }}
- Mission ID: {{ $data['mission_id'] }}
- Completed by: {{ $data['completed_by'] }}
- Completed at: {{ $data['completed_at'] }}

@component('mail::button', ['url' => route('missions.show', $data['mission_id'])])
View Mission Results
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent