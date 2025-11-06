<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 40px;
            text-align: center;
        }
        .content h2 {
            color: #333333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content p {
            color: #666666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .otp-box {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            font-size: 36px;
            font-weight: bold;
            padding: 20px 40px;
            border-radius: 8px;
            letter-spacing: 8px;
            margin: 20px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #999999;
            font-size: 14px;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🌿 অসংশয় | Oshongshoy</h1>
        </div>
        
        <div class="content">
            @if($type === 'registration')
                <h2>Welcome to Oshongshoy!</h2>
                <p>Thank you for registering with us. To complete your registration, please use the verification code below:</p>
            @else
                <h2>Password Reset Request</h2>
                <p>We received a request to reset your password. Use the verification code below to proceed:</p>
            @endif
            
            <div class="otp-box">{{ $otp }}</div>
            
            <div class="warning">
                <strong>⚠️ Important:</strong><br>
                • This code will expire in 10 minutes<br>
                • Do not share this code with anyone<br>
                • If you didn't request this, please ignore this email
            </div>
            
            @if($type === 'registration')
                <p>After verification, you'll be able to access all features of Oshongshoy.</p>
            @else
                <p>After verification, you'll be able to set a new password for your account.</p>
            @endif
        </div>
        
        <div class="footer">
            <p>
                This email was sent from <a href="https://oshongshoy.com">Oshongshoy</a><br>
                যেখানে সংশয় নেই — আছে সাহস, যুক্তি ও ভালোবাসা<br>
                <br>
                © {{ date('Y') }} Oshongshoy. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
