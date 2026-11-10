<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Transaction;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('demo-payment' ,[
            'title' => 'Demo Payment',
            'order' => $request->session()->get('demo_order'),        
            ]);
    }


    public function pay(Request $request)
    {
      $order =$request->session()->get('demo_order');

      if ($order) {
        if ($order['status'] === 'paid') {
            return redirect()->route('demo-payment.index');
        }
        return redirect()->away($order['payment_url']);
      }

      $order_code = 'DEMO-'. Str::uuid();
      $amount = 10000;

      try {
        $this->configMidtrans();
        $payment = Snap::createTransaction([
            'transaction_details' => [
                'order_id' => $order_code,
                'gross_amount' => $amount,
            ],
        ]);
      }
      catch(\Exception $e) {
        report($e);
        return back()->with('error', 'Payment gateway error: ' . $e->getMessage());
    }

        $request->session()->put('demo_order', [
            'order_code' => $order_code,
            'amount' => $amount,
            'status' => 'pending',
            'payment_url' => $payment->redirect_url,
        ]);
    
        return redirect()->away($payment->redirect_url);
    }

    public function check(Request $request)
    {
        $order = $request->session()->get('demo_order');

        if (!$order) {
            return to_route('demo-payment.index')
            ->with('message', 'Buat Transaksi terlebih dahulu');
        }

        try {
            $this->configMidtrans();
            $result = Transaction::status($order['order_code']);
        }
        catch (\Exception $e) {
            report($e);
            return to_route('demo-payment.index')->with('error', 'Gagal memeriksa status pembayaran');
        }
        
        $sameOrder = $result->order_id === $order['order_code'];
        $sameAmount = (float) $result->gross_amount === (float) $order['amount'];
        
        if (!$sameOrder || !$sameAmount) {
            return to_route('demo-payment.index')->with('message', 'status tidak sesuai. Pastikan metode pembayaran yang digunakan sama dengan yang digunakan saat melakukan pembayaran');
        }
        
        $midtransStatus = $result->transaction_status ?? 'unknown';
         if ($midtransStatus === 'settlement' || $midtransStatus === 'capture') {
            $order['status'] = 'paid';
            $request->session()->put('demo_order', $order);
            return to_route('demo-payment.index')->with('message', 'Pembayaran berhasil');
    }

        return to_route('demo-payment.index')->with('message', 'Pembayaran belum berhasil');
    }

    private function configMidtrans()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = false;
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
