@component('mail::message')
# Checklist Completed

Hello {{ $user->name }},

The checklist **{{ $data['checklist_name'] }}** has been completed for the mission at {{ $data['property_name'] }}.

**Details:**
- Property: {{ $data['property_name'] }}
- Checklist: {{ $data['checklist_name'] }}
- Mission ID: {{ $data['mission_id'] }}
- Completed by: {{ $data['completed_by'] }}

@component('mail::button', ['url' => route('missions.show', $data['mission_id'])])
View Mission Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent