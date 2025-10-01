@component('mail::message')
# {{ $title }}

{{ $message }}

@if($requires_action)
@component('mail::button', ['url' => config('app.url')])
Take Action
@endcomponent
@endif

@if(isset($data['mission_id']))
**Mission Details:**
- Property: {{ $data['property_name'] ?? 'N/A' }}
- Mission ID: {{ $data['mission_id'] }}
@endif

@if(isset($data['checklist_id']))
**Checklist Details:**
- Checklist: {{ $data['checklist_name'] ?? 'N/A' }}
- Checklist ID: {{ $data['checklist_id'] }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent