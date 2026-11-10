

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Demo Pembayaran Tiket</title>
</head>
<body>
    <h1>Demo Pembayaran Tiket</h1>
    <p>Harga tiket: Rp10.000</p>

    @if (session('message'))
        <p>{{ session('message') }}</p>
    @endif

    @if (session('error'))
        <p>{{ session('error') }}</p>
    @endif

    @if ($order)
        <p>Kode pesanan: {{ $order['order_code'] }}</p>
        <p>Status pesanan: <strong>{{ $order['status'] }}</strong></p>
    @endif

    @if (! $order || $order['status'] !== 'paid')
        <form method="POST" action="{{ route('demo-payment.pay') }}">
            @csrf
            <button type="submit">
                {{ $order ? 'Buka kembali halaman bayar' : 'Bayar Rp10.000' }}
            </button>
        </form>
    @endif

    @if ($order)
        <form method="POST" action="{{ route('demo-payment.check') }}">
            @csrf
            <button type="submit">Cek Status</button>
        </form>
    @endif
</body>
</html>
