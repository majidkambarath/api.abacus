<table>
    <thead>
    <tr>
        <th><strong>Business Name</strong></th>
        <th><strong>Business Address</strong></th>
        <th><strong>Landphone Number</strong></th>
        <th><strong>Email</strong></th>
        <th><strong>Location</strong></th>
        <th><strong>Route</strong></th>
        <th><strong>Contact Person</strong></th>
        <th><strong>Contact Person Number</strong></th>
        <th><strong>Services</strong></th>
        <th><strong>Urgency</strong></th>
        <th><strong>Stage</strong></th>
        <th><strong>Classification</strong></th>
        <th><strong>Created By</strong></th>
        <th><strong>Created At</strong></th>
        <th><strong>Job Assigned To</strong></th>
        <th><strong>Lead Source</strong></th>
        <th><strong>Notes</strong></th>
    </tr>
    </thead>
    <tbody>
    @foreach($leads as $lead)
        <tr>
            <td>{{ $lead->business->name }}</td>
            <td>{{ $lead->business->address }}</td>
            <td>{{ $lead->business->landphone }}</td>
            <td>{{ $lead->business->email }}</td>
            <td>{{ $lead->business->location->name }}</td>
            <td>{{ $lead->business->location->route->name }}</td>
            <td>
                @if($lead->business->contacts->isNotEmpty() && isset($lead->business->contacts[0]))
                    {{ $lead->business->contacts[0]->name }}
                @else
                    N/A
                @endif
            </td>
            <td>
                @if($lead->business->contacts->isNotEmpty() && isset($lead->business->contacts[0]))
                    {{ $lead->business->contacts[0]->phone_number }}
                @else
                    N/A
                @endif
            </td>
            <td>{{ implode(', ', $lead->services->pluck('name')->toArray()) }}</td>
            <td>{{ $lead->urgency == 1 ? 'High': 'Low' }}</td>
            <td>{{ $lead->stage->title }}</td>
            <td>{{ $lead->lead_status == 0 ? 'Cold' : ($lead->lead_status == 1 ? 'Warm' : 'Hot') }}</td>
            <td>{{ $lead->user->name }}</td>
            <td>{{ \Carbon\Carbon::parse($lead->created_at)->format('d/m/y') }}</td>
            <td>{{ optional($lead->job)->user->name ?? 'N/A' }}</td>
            <td>
                @if($lead->lead_source_id)
                    {{ $lead->source->title }}
                @endif
            </td>
            <td>{{ $lead->note }}</td>

        </tr>
    @endforeach
    </tbody>
</table>
