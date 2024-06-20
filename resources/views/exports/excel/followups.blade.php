<table>
    <thead>
    <tr>
        <th><strong>Business Name</strong></th>
        <th><strong>Landphone Number</strong></th>
        <th><strong>Email</strong></th>
        <th><strong>Location</strong></th>
        <th><strong>Route</strong></th>
        <th><strong>Contact Person</strong></th>
        <th><strong>Contact Person Number</strong></th>


        <th><strong>Followup Date</strong></th>
        <th><strong>Followup Time</strong></th>
        <th><strong>Followup Reason</strong></th>
        <th><strong>Followup Status</strong></th>
        <th><strong>Contact Type</strong></th>



        <th><strong>Services</strong></th>
        <th><strong>Lead Urgency</strong></th>
        <th><strong>Lead Stage</strong></th>
        <th><strong>Lead Classification</strong></th>
        <th><strong>Lead Created By</strong></th>
        <th><strong>Lead Created At</strong></th>
        <th><strong>Job Assigned To</strong></th>
        <th><strong>Job Created At</strong></th>
        <th><strong>Job Start Date</strong></th>
        <th><strong>Job End Date</strong></th>
        <th><strong>Job Status</strong></th>
        <th><strong>Job Closed Date</strong></th>
    </tr>
    </thead>
    <tbody>
    @foreach($followups as $followup)
        <tr>
            <td>{{ $followup->lead->business->name }}</td>
            <td>{{ $followup->lead->business->landphone }}</td>
            <td>{{ $followup->lead->business->email }}</td>
            <td>{{ $followup->lead->business->location->name }}</td>
            <td>{{ $followup->lead->business->location->route->name }}</td>
            <td>
                @if($followup->lead->business->contacts->isNotEmpty() && isset($followup->lead->business->contacts[0]))
                    {{ $followup->lead->business->contacts[0]->name }}
                @else
                    N/A
                @endif
            </td>
            <td>
                @if($followup->lead->business->contacts->isNotEmpty() && isset($followup->lead->business->contacts[0]))
                    {{ $followup->lead->business->contacts[0]->phone_number }}
                @else
                    N/A
                @endif
            </td>

{{--            // followup date--}}
            <td>{{ \Carbon\Carbon::parse($followup->date)->format('d/m/y') }}</td>
            <td>
                @if(isset($followup->time))
                {{ $followup->time }}
                @endif
            </td>


            <td>{{ $followup->reason->title }}</td>
            <td>
                @if($followup->status == 2)
                    Completed
                @elseif($followup->status == 3)
                    Rescheduled
                @elseif($followup->status == 1)
                    Open
                @else
                    Not Interested
                @endif
            </td>
            <td>
                @if($followup->contact_type == 'call')
                    Call
                @elseif($followup->contact_type == 'online')
                    Online Meeting
                @elseif($followup->contact_type == 'in-person')
                    In-Person
                @endif
            </td>



            <td>{{ implode(', ', $followup->lead->services->pluck('name')->toArray()) }}</td>
            <td>{{ $followup->lead->urgency == 1 ? 'High': 'Low' }}</td>
            <td>{{ $followup->lead->stage->title }}</td>
            <td>{{ $followup->lead->lead_status == 0 ? 'Cold' : ($followup->lead->lead_status == 1 ? 'Warm' : 'Hot') }}</td>
            <td>{{ $followup->lead->user->name }}</td>
            <td>{{ \Carbon\Carbon::parse($followup->lead->created_at)->format('d/m/y') }}</td>
            <td>{{ $followup->job->user->name }}</td>
            <td>{{ \Carbon\Carbon::parse($followup->job->created_at)->format('d/m/y') }}</td>
            <td>{{ \Carbon\Carbon::parse($followup->job->start_date)->format('d/m/y') }}</td>
            <td>{{ \Carbon\Carbon::parse($followup->job->end_date)->format('d/m/y') }}</td>
            <td>{{ $followup->job->status == 1 ? 'Open': 'Closed' }}</td>

            <td>
                @if($followup->job->status != 1)
                    {{ \Carbon\Carbon::parse($followup->job->closed_date)->format('d/m/y') }}
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
