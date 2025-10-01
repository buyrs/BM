@component('mail::message')
# New Mission Assigned

Hello {{ $user->name }},

You have been assigned a new mission for **{{ $data['property_name'] }}**.

**Mission Details:**
- Property: {{ $data['property_name'] }}
- Mission ID: {{ $data['mission_id'] }}
@if(isset($data['due_date']))
- Due Date: {{ $data['due_date'] }}
@endif

@component('mail::button', ['url' => route('missions.show', $data['mission_id'])])
View Mission
@endcomponent

Please complete this mission as soon as possible.

Thanks,<br>
{{ config('app.name') }}
@endcomponent