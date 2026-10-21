<?php
// Chương trình khách hàng thân thiết. Chỉnh các con số cho phù hợp với nhà hàng của bạn.
define('LOYALTY_VND_PER_POINT', 10000);        // cứ 10.000 ₫ thanh toán được 1 điểm
define('LOYALTY_POINT_VALUE', 1000);           // khi dùng, 1 điểm giảm 1.000 ₫
define('LOYALTY_MAX_REDEEM_PERCENT', 50);      // dùng điểm giảm tối đa 50% tạm tính của đơn
define('LOYALTY_MIN_REDEEM', 10);              // mỗi lần dùng tối thiểu 10 điểm
// Hạng thành viên theo TỔNG ĐIỂM ĐÃ TÍCH LŨY (dùng điểm không làm tụt hạng).
// [tên hạng, điểm tích lũy tối thiểu, hệ số nhân điểm khi tích, màu nhãn]. Sắp xếp tăng dần.
define('LOYALTY_TIERS', [
    ['Thành viên', 0,    1.00, 'secondary'],
    ['Bạc',        200,  1.05, 'info'],
    ['Vàng',       500,  1.10, 'warning'],
    ['Kim cương',  1000, 1.20, 'primary'],
]);
