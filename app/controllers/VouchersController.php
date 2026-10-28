<?php
require_once APP_PATH . '/models/Voucher.php';

class VouchersController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'manager');
        $this->view('vouchers/index', ['title' => 'Quản lý voucher', 'vouchers' => Voucher::all()]);
    }

    public function create(): void
    {
        $this->requireRole('admin', 'manager');
        $voucher = $this->emptyVoucher();
        $this->form(null, $voucher);
    }

    public function edit(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $voucher = Voucher::find((int)$id);
        if (!$voucher) $this->forbidden();
        $this->form((int)$id, $voucher);
    }

    public function toggle(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('vouchers');
        $voucher = Voucher::find((int)$id);
        if (!$voucher) $this->forbidden();
        $active = !(bool)$voucher['is_active'];
        Voucher::setActive((int)$id, $active);
        flash('success', $active ? 'Đã kích hoạt voucher.' : 'Đã tạm khóa voucher.');
        redirect('vouchers');
    }

    public function delete(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('vouchers');
        $voucher = Voucher::find((int)$id);
        if (!$voucher) $this->forbidden();

        $deleted = Voucher::deleteOrDeactivate((int)$id);
        flash('success', $deleted
            ? 'Đã xóa voucher ' . $voucher['code'] . ' vì chưa được sử dụng.'
            : 'Voucher ' . $voucher['code'] . ' đã có đơn sử dụng nên được khóa để giữ lịch sử.');
        redirect('vouchers');
    }

    private function form(?int $id, array $voucher): void
    {
        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->readVoucher();
            $voucher = array_merge($voucher, $data);
            if (!$err) {
                try {
                    Voucher::save($id, $data, (int)auth_user()['id']);
                    flash('success', $id === null ? 'Đã tạo voucher.' : 'Đã cập nhật voucher.');
                    redirect('vouchers');
                } catch (PDOException $e) {
                    if ((string)$e->getCode() === '23000') $err = 'Mã voucher đã được sử dụng. Hãy chọn mã khác.';
                    else throw $e;
                }
            }
            flash('error', $err);
        }
        $this->view('vouchers/form', [
            'title' => $id === null ? 'Tạo voucher' : 'Sửa voucher',
            'mode' => $id === null ? 'create' : 'edit', 'voucher' => $voucher,
        ]);
    }

    private function readVoucher(): array
    {
        $code = strtoupper(trim($this->input('code')));
        $description = trim($this->input('description'));
        $type = $this->input('discount_type');
        $value = filter_var($this->input('discount_value'), FILTER_VALIDATE_INT);
        $minSubtotal = filter_var($this->input('min_subtotal', '0'), FILTER_VALIDATE_INT);
        $maxRaw = trim($this->input('max_discount'));
        $maxDiscount = $maxRaw === '' ? null : filter_var($maxRaw, FILTER_VALIDATE_INT);
        $limitRaw = trim($this->input('usage_limit'));
        $usageLimit = $limitRaw === '' ? null : filter_var($limitRaw, FILTER_VALIDATE_INT);
        $start = $this->parseDate($this->input('starts_at'));
        $end = $this->parseDate($this->input('ends_at'));
        $active = isset($_POST['is_active']) ? 1 : 0;

        $err = null;
        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{2,39}$/', $code)) $err = 'Mã voucher gồm 3-40 ký tự A-Z, số, gạch ngang hoặc gạch dưới.';
        elseif ($description === '' || mb_strlen($description) > 255) $err = 'Mô tả voucher không được để trống và tối đa 255 ký tự.';
        elseif (!in_array($type, ['percent', 'fixed'], true)) $err = 'Loại giảm giá không hợp lệ.';
        elseif ($value === false || $value < 1 || $value > 999999999999 || ($type === 'percent' && $value > 100)) $err = 'Giá trị giảm giá không hợp lệ.';
        elseif ($minSubtotal === false || $minSubtotal < 0 || $minSubtotal > 999999999999) $err = 'Giá trị đơn tối thiểu không hợp lệ.';
        elseif ($maxRaw !== '' && ($maxDiscount === false || $maxDiscount < 1 || $maxDiscount > 999999999999)) $err = 'Mức giảm tối đa không hợp lệ.';
        elseif ($limitRaw !== '' && ($usageLimit === false || $usageLimit < 1 || $usageLimit > 2147483647)) $err = 'Tổng lượt sử dụng phải từ 1 trở lên.';
        elseif (!$start || !$end || $end <= $start) $err = 'Thời gian bắt đầu/kết thúc không hợp lệ.';

        return [[
            'code' => $code, 'description' => $description, 'discount_type' => $type,
            'discount_value' => (int)$value, 'min_subtotal' => (int)$minSubtotal,
            'max_discount' => $maxDiscount === false ? null : $maxDiscount,
            'starts_at' => $start ? $start->format('Y-m-d H:i:s') : '',
            'ends_at' => $end ? $end->format('Y-m-d H:i:s') : '',
            'usage_limit' => $usageLimit === false ? null : $usageLimit, 'is_active' => $active,
        ], $err];
    }

    private function parseDate(string $value): ?DateTime
    {
        $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value);
        $errors = DateTime::getLastErrors();
        return $date && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) ? $date : null;
    }

    private function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        return is_scalar($value) ? (string)$value : '';
    }

    private function emptyVoucher(): array
    {
        return [
            'code' => '', 'description' => '', 'discount_type' => 'percent', 'discount_value' => '',
            'min_subtotal' => 0, 'max_discount' => '', 'starts_at' => date('Y-m-d\TH:i'),
            'ends_at' => date('Y-m-d\TH:i', strtotime('+30 days')), 'usage_limit' => '', 'is_active' => 1,
        ];
    }
}
