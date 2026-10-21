<?php
// Thiết lập đặt bàn online (trang công khai cho khách)
define('BOOK_FIRST_HOUR', 9);              // khung giờ đặt sớm nhất: 09:00
define('BOOK_LAST_HOUR', 21);              // khung giờ đặt muộn nhất: 21:00
define('BOOK_SLOT_MIN', 30);               // mỗi khung cách nhau 30 phút
define('BOOK_MIN_LEAD_MIN', 60);           // phải đặt trước ít nhất 60 phút
define('BOOK_MAX_DAYS', 60);               // chỉ nhận đặt trong 60 ngày tới
define('BOOK_MAX_GUESTS', 20);             // quá số này khách phải gọi điện
define('BOOK_CANCEL_BEFORE_MIN', 120);     // khách tự hủy được nếu còn ít nhất 120 phút nữa mới tới giờ hẹn
define('BOOK_MAX_PER_IP_HOUR', 5);         // chống spam: tối đa 5 lượt đặt mỗi giờ từ một địa chỉ IP
define('BOOK_MAX_ACTIVE_PER_PHONE', 3);    // mỗi số điện thoại tối đa 3 lượt đặt còn hiệu lực
