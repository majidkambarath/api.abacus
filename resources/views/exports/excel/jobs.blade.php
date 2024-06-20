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
        <th><strong>Lead Urgency</strong></th>
        <th><strong>Lead Stage</strong></th>
        <th><strong>Lead Classification</strong></th>
        <th><strong>Lead Created By</strong></th>
        <th><strong>Lead Created At</strong></th>
        <th><strong>Job Assigned To</strong></th>
        <th><strong>Total Followups</strong></th>
        <th><strong>Job Created At</strong></th>
        <th><strong>Job Start Date</strong></th>
        <th><strong>Job End Date</strong></th>
        <th><strong>Job Status</strong></th>
        <th><strong>Job Closed Date</strong></th>
        <th><strong>Lead Source</strong></th>
    </tr>
    </thead>
    <tbody>
    @foreach($jobs as $job)
        <tr>
            <td>{{ $job->lead->business->name }}</td>
            <td>{{ $job->lead->business->address }}</td>
            <td>{{ $job->lead->business->landphone }}</td>
            <td>{{ $job->lead->business->email }}</td>
            <td>{{ $job->lead->business->location->name }}</td>
            <td>{{ $job->lead->business->location->route->name }}</td>
            <td>
                @if($job->lead->business->contacts->isNotEmpty() && isset($job->lead->business->contacts[0]))
                    {{ $job->lead->business->contacts[0]->name }}
                @else
                    N/A
                @endif
            </td>
            <td>
                @if($job->lead->business->contacts->isNotEmpty() && isset($job->lead->business->contacts[0]))
                    {{ $job->lead->business->contacts[0]->phone_number }}
                @else
                    N/A
                @endif
            </td>
            <td>{{ implode(', ', $job->lead->services->pluck('name')->toArray()) }}</td>
            <td>{{ $job->lead->urgency == 1 ? 'High': 'Low' }}</td>
            <td>{{ $job->lead->stage->title }}</td>
            <td>{{ $job->lead->lead_status == 0 ? 'Cold' : ($job->lead->lead_status == 1 ? 'Warm' : 'Hot') }}</td>
            <td>{{ $job->lead->user->name }}</td>
            <td>{{ \Carbon\Carbon::parse($job->lead->created_at)->format('d/m/y') }}</td>
            <td>{{ $job->user->name }}</td>
            <td>{{ count($job->followups) }}</td>
            <td>{{ \Carbon\Carbon::parse($job->created_at)->format('d/m/y') }}</td>
            <td>{{ \Carbon\Carbon::parse($job->start_date)->format('d/m/y') }}</td>
            <td>{{ \Carbon\Carbon::parse($job->end_date)->format('d/m/y') }}</td>
            <td>{{ $job->status == 1 ? 'Open': 'Closed' }}</td>

            <td>
                @if($job->status != 1)
                    {{ \Carbon\Carbon::parse($job->closed_date)->format('d/m/y') }}
                @endif
            </td>
            <td>
                @if($job->lead->lead_source_id)
                    {{ $job->lead->source->title }}
                @endif
            </td>
        </tr>
    @endforeach
</tbody>
</table>
