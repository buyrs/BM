@component('mail::message')
# Maintenance Request Approved

Hello {{ $user->name }},

@if(isset($data['approved_by']))
Your maintenance request for **{{ $data['property_name'] }}** has been approved by {{ $data['approved_by'] }}.
@else
A maintenance request has been approved and assigned to you for **{{ $data['property_name'] }}**.
@endif

**Priority:** {{ ucfirst($data['priority']) }}
@if(isset($data['estimated_cost']) && $data['estimated_cost'])
**Estimated Cost:** ${{ number_format($data['estimated_cost'], 2) }}
@endif

@component('mail::button', ['url' => route('ops.maintenance-requests.show', $data['maintenance_request_id'])])
View Maintenance Request
@endcomponent

@if($requires_action)
Please proceed with the maintenance work as soon as possible.
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent