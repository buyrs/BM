@component('mail::message')
# New Maintenance Request

Hello {{ $user->name }},

A new maintenance request has been submitted and requires your attention.

**Property:** {{ $data['property_name'] }}
**Priority:** {{ ucfirst($data['priority']) }}
**Reported by:** {{ $data['reported_by'] }}
**Status:** {{ ucfirst($data['status']) }}

@if(isset($data['estimated_cost']) && $data['estimated_cost'])
**Estimated Cost:** ${{ number_format($data['estimated_cost'], 2) }}
@endif

@component('mail::button', ['url' => route('ops.maintenance-requests.show', $data['maintenance_request_id'])])
View Maintenance Request
@endcomponent

Please review and take appropriate action.

Thanks,<br>
{{ config('app.name') }}
@endcomponent