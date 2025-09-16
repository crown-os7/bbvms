<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Minute of Meeting</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <th colspan="2">Minute of Meeting - {{ $meeting['visitors'][0]['company'] ?? '-' }}</th>
        </tr>
        <tr>
            <td><b>Ruangan</b></td>
            <td>{{ $meeting['room']['name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td><b>Tanggal</b></td>
            <td>{{ $meeting['date'] }}</td>
        </tr>
        <tr>
            <td><b>Jam Mulai</b></td>
            <td>{{ $meeting['start_time'] }}</td>
        </tr>
        <tr>
            <td><b>Jam Selesai</b></td>
            <td>{{ $meeting['end_time'] }}</td>
        </tr>
        <tr>
            <td><b>Bertemu dengan</b></td>
            <td>{{ $meeting['meeting_with']['name'] ?? '-' }}</td>
        </tr>
    </table>

    <br>

    <table>
        <tr>
            <th>Tamu</th>
            <th>Status</th>
        </tr>
        @foreach($meeting['visitors'] as $visitor)
            <tr>
                <td>{{ $visitor['name'] }} - {{ $visitor['company'] }}</td>
                <td>{{ $visitor['status'] }}</td>
            </tr>
        @endforeach
    </table>

    <br>

    <table>
        <tr>
            <th>Details</th>
        </tr>
        <tr>
            <td>{!! $meeting['minute_of_meeting']->details ?? '-' !!}</td>
            {{-- <td>{{ $meeting['minute_of_meeting']->details ?? '-' }}</td> --}}
        </tr>
    </table>
</body>
</html>
