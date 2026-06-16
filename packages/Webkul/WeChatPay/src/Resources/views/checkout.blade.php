<!DOCTYPE html>
<html>
<head>
    <title>{{ trans('wechatpay::app.title') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .qrcode {
            margin: 20px 0;
        }
        .qrcode img {
            max-width: 300px;
            max-height: 300px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #07c160;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #06ad56;
        }
        .cancel-btn {
            background: #f0f0f0;
            color: #333;
            margin-left: 10px;
        }
        .cancel-btn:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ trans('wechatpay::app.title') }}</h1>
        <p>{{ trans('wechatpay::app.scan-qr') }}</p>

        <div class="qrcode">
            @if($code_url)
                <div id="qrcode"></div>
                <p style="font-size: 12px; color: #999; word-break: break-all;">{{ $code_url }}</p>
            @endif
        </div>

        <div>
            <a href="{{ route('wechatpay.payment.success', ['out_trade_no' => $out_trade_no]) }}" class="btn">
                {{ trans('wechatpay::app.payment-complete') }}
            </a>
            <a href="{{ route('wechatpay.payment.cancel') }}" class="btn cancel-btn">
                {{ trans('wechatpay::app.cancel') }}
            </a>
        </div>
    </div>

    @if($code_url)
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
            new QRCode(document.getElementById('qrcode'), {
                text: '{{ $code_url }}',
                width: 256,
                height: 256,
            });
        </script>
    @endif
</body>
</html>
