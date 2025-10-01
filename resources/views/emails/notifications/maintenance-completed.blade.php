@component('mail::message')
# Maintenance Request Completed

Hello {{ $user->name }},

Your maintenance request for **{{ $data['property_name'] }}** has been completed by {{ $data['completed_by'] }}.

@if(isset($data['completion_notes']) && $data['completion_notes'])
**Completion Notes:**
{{ $data['completion_notes'] }}
@endif

@component('mail::button', ['url' => route('ops.maintenance-requests.show', $data['maintenance_request_id'])])
View Maintenance Request
@endcomponent

Thank you for reporting this issue.

Thanks,<br>
{{ config('app.name') }}
@endcomponent