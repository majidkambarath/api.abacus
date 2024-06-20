<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Daily Reports - Abacus</title>
    <style type="text/css">
        body {
            font-family: "Arial", sans-serif;
        }

        p {
            font-size: 12px;
        }
        table.leads {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        thead {
            background: #eee;
        }
        table.leads td, table.leads th  {
            border: 1px solid #000;
            font-size: 12px;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
<img src="http://lead-abacus.com/images/login-logo.png" alt="">
<hr>
<table style="width: 75%; margin-bottom: 32px">
    <tbody>
    <tr>
        <td style="width: 33.33%"><p>DATE: </p></td>
        <td style="width: 33.33%"><p>STARTING ODOMETER: </p></td>
        <td style="width: 33.33%"><p>DISTANCE TRAVELED: </p></td>
    </tr>
    <tr>
        <td style="width: 33.33%"><p>NAME: <strong style="font-size: 14px">{{$user['name']}}</strong></p></td>
        <td style="width: 33.33%"><p>ENDING ODOMETER: </p></td>
        <td style="width: 33.33%"><p>TOTAL CUSTOMER VISITED:</p></td>
    </tr>
    </tbody>
</table>
<table class="leads">
    <thead>
    <tr>
        <th>SL.NO</th>
        <th>COMPANY NAME</th>
        <th>SERVICE REQUIRED</th>
        <th>LEAD STATUS<br/> (HOT/WARM/<br/>COLD)</th>
        <th>CONTACT PERSON</th>
        <th>CONTACT NUMBER</th>
        <th>VISIT STATUS</th>
        <th>ADDRESS</th>
    </tr>
    </thead>
    <tbody>
    @foreach($leads as $key => $lead)
        <tr>

                <td>{{$key+1}}</td>
                <td>{{ $lead['business']['name'] }}</td>
                <td>{{ implode(', ', array_column($lead['services'], 'name')) }}</td>
                <td>{{ $lead['lead_status'] == 0 ? 'Cold' : ($lead['lead_status'] == 1 ? 'Warm' : 'Hot') }}</td>
                <td>
                    @if(count($lead['business']['contacts']) && isset($lead['business']['contacts'][0]))
                        {{ $lead['business']['contacts'][0]['name'] }}
                    @endif
                </td>
                <td>
                    @if(count($lead['business']['contacts']) && isset($lead['business']['contacts'][0]))
                        {{ $lead['business']['contacts'][0]['phone_number'] }}
                    @endif
                </td>
                <td>

                </td>
                <td>
                    {{$lead['business']['address']}}
                </td>
        </tr>
    @endforeach

    </tbody>
</table>

</body>
</html>


