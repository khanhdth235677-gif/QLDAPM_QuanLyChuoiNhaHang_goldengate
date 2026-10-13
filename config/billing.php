<?php
// Thiết lập thanh toán. Hãy hỏi kế toán về mức VAT hiện hành rồi chỉnh ở đây.
define('VAT_DEFAULT', 10);                       // % VAT mặc định hiện sẵn ở màn hình thanh toán
define('VAT_OPTIONS', [0, 5, 8, 10]);            // các mức VAT cho thu ngân chọn
define('MAX_CASHIER_DISCOUNT_PERCENT', 10);      // thu ngân giảm tối đa bao nhiêu % tạm tính (quản lý, admin không giới hạn)
