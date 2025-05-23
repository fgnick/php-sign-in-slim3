<?php
/**
 * When you turn on the outsourcing access list and what URL belongs to
 */
return [
    'your issuer name on internal JWT token' => [
        'uid' => '{ You have to give an random code string as a JWT jti }',
        'path' => [
            '/path/you/allow/to/access'
            // 原本是 /api/v1/... 但是因為htaccess檔案設定的關係，所以api的開頭會消失。
            // 但是當程式放到server去的時候，因為server有將 /api/ 這樣轉址，因此在GCP上的時候，
            // 這個網址必須要將basePath的字串加上，最後等於 /api/v1/outsource/open-api/remote/cmd 才對！
            // .... 以後就是一個一個新增下去。如果不再這列表中的 URL，則不給予進入。
        ]
    ]


];
