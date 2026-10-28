<?php
// Tải ảnh lên an toàn: kiểm tra loại thật của tệp, giới hạn dung lượng, đặt tên ngẫu nhiên
class Upload
{
    const MAX_BYTES = 2097152;   // 2 MB
 
    // Trả về [đường dẫn tương đối trong assets/ hoặc null, thông báo lỗi hoặc null]
    public static function image(?array $f, string $subdir): array
    {
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
        if ($f['error'] !== UPLOAD_ERR_OK)         return [null, 'Tải ảnh lên thất bại (mã lỗi ' . $f['error'] . ').'];
        if ($f['size'] > self::MAX_BYTES)          return [null, 'Ảnh quá lớn, tối đa 2 MB.'];
        if (!is_uploaded_file($f['tmp_name']))     return [null, 'Tệp tải lên không hợp lệ.'];

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext || @getimagesize($f['tmp_name']) === false) return [null, 'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.'];

        $dir = ROOT_PATH . '/assets/uploads/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) return [null, 'Không tạo được thư mục lưu ảnh.'];

        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) return [null, 'Không lưu được ảnh.'];
        return ['uploads/' . $subdir . '/' . $name, null];
    }

    public static function delete(?string $rel): void
    {
        if (!$rel || strpos($rel, 'uploads/') !== 0 || strpos($rel, '..') !== false) return;
        $path = ROOT_PATH . '/assets/' . $rel;
        if (is_file($path)) @unlink($path);
    }
}
