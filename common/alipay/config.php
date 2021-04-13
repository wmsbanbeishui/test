<?php
$config = array (	
		//应用ID,您的APPID。
		'app_id' => "",

		//商户私钥
		'merchant_private_key' => "+Hr4ENek9u8OAPHbLmUw0D5RV7IrcOA2J/5MpORuPDZ8cEQOIw+rTzd9ij9MfforTbVU088uttnUiclT3ZKeTpoYokJYgMQJthaSpy14gZHvQMhN5YgyfmHPudjCLvHuyrQfo38T9oiCnx5qGLXncXjSuDeS4zoYaLvA8FCvDMw+QCZS7qvUh44wBC5XQ1tw92LTXvAQlh27vQMyiq++n78lsMLsZzsnKCf/IjW8r4SWKAKaSl/B38LqzVxKxDNbpoUYWtxwC2T8zea6fgCIdxwUzS8eNaR/d9k0tPrQC6zGYX9bV0Y/MFCwwIDAQABAoIBAGs32Q8BBsaD1h8IUYa3soCPDQ8esF1zrE0lD0807W7mgHm2Ny5mn9zNV2KSXv8iwIS9gF+z4GBr3vklGNRVwjYmQIAzPOiifWzGC6Hx69F/I7e3HXKrLxY2vaDzYYxG/KNH5tSM4UrF5r8NOlPI9HaJtM2fBNqorQDYaLCE602CwG23pt5RX535chxwj4qmJghWjqbq/ToVZr0ARhIU/cbnErreN1fbN+CLym/LgbI7dRg6Q3Cj3ETmV98/B27dfxkg/U2QO3R1MBRSk2BMZhDtukkISNwApzsWk8ljiH/25nwMh1WmMGbBCeErsgJqZHMar9W+eqEldr4PhijTp6ECgYEA+5ODb2x0+uVjY+YM2ddcrY6WmXCFKacGp3yoU8IkbBE5VS4QZTm49Dt/PsDbtQ7IrtLvejtO96O0PVaL3n7I29/BSnN6FGk7Aq0VHwAE1sgIq4kr5EmegjcRqBSHr3iRkSqScJCDOODQBpfWvh0PE6FZkyc0HZ20VHIKIsES7WsCgYEAzmfvLDOt1BvR7c/380M3ODtFH+CeMstXzf6cVV330fICUH9S+dzgNMlkBBaDDqiLcL0E0ol7r1briTCGGxB9Zyhwp85XNn38Imal1vDKyHfYjEvQDqsJ4ifEBKRWSiNOAa+H1Sd8FOZM4X4NU962SJwmVEBsdYhZ/UJl3Q+FPgkCgYEAqaahlOs7u9IjA0Qo2GKOAhBM6K5jbmJPb3T2An3CmAnJcvK8ZbbWTgUtWwEtaFzO4m4mxnPmXcNMkayiZ+lxxCyRKYbUBZ2tCLH1s1EM8lY02pCHQ8yNktxWENW/ZopVB+MAm94oT5vzTO7qBoyMFT3SrRRb5bjq2aOJyEQRYn8CgYEAwggiZkMOnd6pMg3W4O/G8S2Ghbj5/nX5TMSU4gs79Di0xEdtEUX53qWTR+SBvz3iF1EZP3HOu73SHV1oM/kEaf0yKg1nHurAIvar36rsdhdzki+SnrdayybmthZmp7sYka0Y2+AKXPtCOpfsn3M1mHxx67HEQ5iyP8ozR+RXNUECgYEA3jxk4rZ4nnZJqO1/J33XuKB6g5R7AOFTFKLFPhD+0VTr0L3FDenZ3q4n557YTxaQ+pVIMPyfh+VLmA+VOk4+6yxqjusJQHjPWYZwblW315EJtykF4R9D93v7SLsse2bnXVmqTxSZF8/5Smh7rI2xaPTxZV4iz0jqhdNAAGEssDA=",
		
		//异步通知地址
		'notify_url' => "http://129.204.136.4/api/ali-pay-notify",
		
		//同步跳转
		'return_url' => "http://129.204.136.4/alipay.trade.page.pay-PHP-UTF-8/return_url.php",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "+Hr4ENek9u8OAPHbLmUw0D5RV7IrcOA2J/5MpORuPDZ8cEQOIw+rTzd9ij9MfforTbVU088uttnUiclT3ZKeTpoYokJYgMQJthaSpy14gZHvQMhN5YgyfmHPudjCLvHuyrQfo38T9oiCnx5qGLXncXjSuDeS4zoYaLvA8FCvDMw+QCZS7qvUh44wBC5XQ1tw92LTXvAQlh27vQMyiq++n78lsMLsZzsnKCf/IjW8r4SWKAKaSl/B38LqzVxKxDNbpoUYWtxwC2T8zea6fgCIdxwUzS8eNaR/d9k0tPrQC6zGYX9bV0Y/MFCwwIDAQAB",
);