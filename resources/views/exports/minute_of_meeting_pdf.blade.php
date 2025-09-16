<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Minute of Meeting</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            h2, h3 { text-align: center; margin: 8px 0; }
            table { border-collapse: collapse; width: 100%; margin: 10px 0; }
            table, th, td { border: 1px solid black; }
            th, td { padding: 6px; text-align: left; vertical-align: top; }

            /* 🔹 Perapihan konten Quill */
            .mom-content p {
                margin: 0; /* hapus semua margin */
                padding: 0;
                line-height: 1.4;
            }
            .mom-content ul, 
            .mom-content ol {
                margin: 0 0 0 50px; /* rapat */
                padding: 0;
            }
            .mom-content li {
                margin: 0;
                padding: 0;
            }
        </style>

</head>
<body>
    <h2>Minute of Meeting - {{ $meeting['visitors'][0]['company'] ?? '-' }}</h2>

    <!-- Info Meeting -->
    <table>
        <tr>
            <th style="width: 30%">Ruangan</th>
            <td>{{ $meeting['room']['name'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Tanggal</th>
            <td>{{ $meeting['date'] }}</td>
        </tr>
        <tr>
            <th>Jam Mulai</th>
            <td>{{ $meeting['start_time'] }}</td>
        </tr>
        <tr>
            <th>Jam Selesai</th>
            <td>{{ $meeting['end_time'] }}</td>
        </tr>
        <tr>
            <th>Bertemu dengan</th>
            <td>{{ $meeting['meeting_with']['name'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Purpose</th>
            <td>{{ $meeting['purpose'] ?? '-' }}</td>
        </tr>
    </table>

    <!-- Daftar Tamu -->
    <h2>Tamu & Perusahaan</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th>Nama</th>
                <th>Perusahaan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($meeting['visitors'] as $i => $visitor)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $visitor['name'] }}</td>
                    <td>{{ $visitor['company'] }}</td>
                    <td>{{ $visitor['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Detail MOM -->
    <h2>Details</h2>
    <div class="mom-content">
        {!! $meeting['minute_of_meeting']->details ?? '-' !!}
    </div>
</body>
</html>
