<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/Inventory.php';

// Kho nguyên liệu: admin và quản lý chi nhánh
class InventoryController extends Controller
{
    const UNITS = ['kg', 'g', 'lít', 'ml', 'cái', 'hộp', 'chai', 'lon', 'gói', 'bó', 'quả', 'thùng'];

    // Tồn kho theo chi nhánh
    public function index(): void
    {
        $this->requireRole('admin', 'manager');
        [$bid, $branches] = $this->context();
        $low = !empty($_GET['low']);

        $rows = $bid ? Inventory::stock($bid, $low) : [];
        $all  = $bid ? ($low ? Inventory::stock($bid, false) : $rows) : [];
        $lowCount = 0; $outCount = 0;
        foreach ($all as $r) {
            if ((float)$r['quantity'] <= 0) $outCount++;
            if ((float)$r['min_quantity'] > 0 && (float)$r['quantity'] <= (float)$r['min_quantity']) $lowCount++;
        }

        $this->view('inventory/index', [
            'title' => 'Kho nguyên liệu', 'rows' => $rows, 'bid' => $bid, 'branches' => $branches,
            'isAdmin' => has_role('admin'), 'low' => $low, 'total' => count($all), 'lowCount' => $lowCount, 'outCount' => $outCount,
        ]);
    }

    // Phiếu nhập, xuất, kiểm kê
    public function move(): void
    {
        $this->requireRole('admin', 'manager');
        [$bid] = $this->context();
        if (!$bid) { flash('error', 'Chưa có chi nhánh nào.'); redirect('branches'); }
        $stock = Inventory::stock($bid);
        if (!$stock) { flash('error', 'Chưa có nguyên liệu nào. Hãy thêm nguyên liệu trước.'); redirect('inventory/ingredients'); }

        $type = $_GET['type'] ?? 'in';
        $form = ['type' => isset(Inventory::TYPES[$type]) ? $type : 'in', 'ingredient_id' => (int)($_GET['ingredient'] ?? 0), 'quantity' => '', 'note' => ''];

        if ($this->isPost()) {
            csrf_check();
            $form = ['type' => $_POST['type'] ?? '', 'ingredient_id' => (int)($_POST['ingredient_id'] ?? 0),
                     'quantity' => trim($_POST['quantity'] ?? ''), 'note' => trim($_POST['note'] ?? '')];

            $ing = null;
            foreach ($stock as $s) if ((int)$s['id'] === $form['ingredient_id']) $ing = $s;
            $qty = $this->qty($form['quantity'], $form['type'] === 'adjust');

            $err = null;
            if (!isset(Inventory::TYPES[$form['type']])) $err = 'Loại phiếu không hợp lệ.';
            elseif (!$ing)                               $err = 'Vui lòng chọn nguyên liệu.';
            elseif ($qty === null)                       $err = 'Số lượng không hợp lệ. Dùng số dương, tối đa 2 chữ số thập phân, ví dụ 12,5 (không dùng dấu ngăn nghìn).';
            elseif (mb_strlen($form['note']) > 255)      $err = 'Ghi chú tối đa 255 ký tự.';

            if (!$err) $err = Inventory::move($bid, (int)$ing['id'], $form['type'], $qty, $form['note'] !== '' ? $form['note'] : null, (int)auth_user()['id']);
            if (!$err) {
                flash('success', Inventory::TYPES[$form['type']] . ' ' . $ing['name'] . ' thành công.');
                redirect('inventory' . (has_role('admin') ? '?branch=' . $bid : ''));
            }
            flash('error', $err);
        }

        $branch = Branch::find($bid);
        $this->view('inventory/move', ['title' => 'Phiếu kho', 'form' => $form, 'stock' => $stock, 'bid' => $bid, 'branchName' => $branch['name'] ?? '']);
    }

    public function setmin(): void
    {
        $this->requireRole('admin', 'manager');
        $this->postOnly('inventory');
        [$bid] = $this->context();
        $ingId = (int)($_POST['ingredient_id'] ?? 0);
        $min = $this->qty(trim($_POST['min'] ?? ''), true);

        if (!$bid || !Inventory::findIngredient($ingId)) flash('error', 'Nguyên liệu không hợp lệ.');
        elseif ($min === null)                          flash('error', 'Mức tối thiểu không hợp lệ.');
        else { Inventory::setMin($bid, $ingId, $min); flash('success', 'Đã lưu mức tối thiểu.'); }
        redirect('inventory' . (has_role('admin') ? '?branch=' . $bid : ''));
    }

    // Lịch sử nhập xuất
    public function history(): void
    {
        $this->requireRole('admin', 'manager');
        [$bid, $branches] = $this->context(true);

        $from = $this->date($_GET['from'] ?? '', date('Y-m-d', strtotime('-6 days')));
        $to   = $this->date($_GET['to'] ?? '', date('Y-m-d'));
        if ($from > $to) [$from, $to] = [$to, $from];
        if (strtotime($to) - strtotime($from) > 366 * 86400) $from = date('Y-m-d', strtotime($to . ' -366 days'));

        $type = $_GET['type'] ?? 'all';
        $ingId = (int)($_GET['ingredient'] ?? 0);
        $rows = Inventory::history($bid ?: null, $ingId ?: null, $type, $from, $to);

        $this->view('inventory/history', [
            'title' => 'Lịch sử kho', 'rows' => $rows, 'bid' => $bid, 'branches' => $branches, 'isAdmin' => has_role('admin'),
            'from' => $from, 'to' => $to, 'type' => $type, 'ingId' => $ingId, 'ingredients' => Inventory::ingredients(), 'limit' => 300,
        ]);
    }

    // Danh mục nguyên liệu (dùng chung toàn chuỗi)
    public function ingredients(): void
    {
        $this->requireRole('admin', 'manager');
        $this->view('inventory/ingredients', ['title' => 'Nguyên liệu', 'items' => Inventory::ingredients(), 'isAdmin' => has_role('admin'), 'units' => self::UNITS]);
    }

    // Thêm (admin, quản lý) hoặc sửa (chỉ admin) nguyên liệu
    public function saveingredient(string $id = ''): void
    {
        if ($id === '') $this->requireRole('admin', 'manager'); else $this->requireRole('admin');
        $this->postOnly('inventory/ingredients');

        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $ingId = $id === '' ? null : (int)$id;

        if ($name === '' || mb_strlen($name) > 100)  flash('error', 'Tên nguyên liệu không hợp lệ (tối đa 100 ký tự).');
        elseif ($unit === '' || mb_strlen($unit) > 20) flash('error', 'Đơn vị không hợp lệ (tối đa 20 ký tự).');
        elseif (Inventory::nameExists($name, $ingId))  flash('error', 'Nguyên liệu "' . $name . '" đã tồn tại.');
        elseif ($ingId !== null && !Inventory::findIngredient($ingId)) $this->forbidden();
        elseif ($ingId === null) { Inventory::createIngredient($name, $unit); flash('success', 'Đã thêm nguyên liệu ' . $name . '. Hãy nhập kho và đặt mức tối thiểu cho từng chi nhánh.'); }
        else                     { Inventory::updateIngredient($ingId, $name, $unit);  flash('success', 'Đã cập nhật nguyên liệu ' . $name . '.'); }
        redirect('inventory/ingredients');
    }

    public function deleteingredient(string $id = ''): void
    {
        $this->requireRole('admin');
        $this->postOnly('inventory/ingredients');
        $ing = Inventory::findIngredient((int)$id);
        if (!$ing) $this->forbidden();

        if (Recipe::usedCount((int)$id) > 0) {
            flash('error', 'Nguyên liệu "' . $ing['name'] . '" đang được dùng trong công thức món. Hãy gỡ khỏi công thức trước khi xóa.');
        } elseif ((int)$ing['moves'] > 0 || (float)$ing['total_stock'] > 0) {
            flash('error', 'Nguyên liệu "' . $ing['name'] . '" đã có tồn kho hoặc lịch sử nhập xuất nên không thể xóa.');
        } else {
            Inventory::deleteIngredient((int)$id);
            flash('success', 'Đã xóa nguyên liệu ' . $ing['name'] . '.');
        }
        redirect('inventory/ingredients');
    }

    // ---------- nội bộ ----------
    // Chi nhánh đang làm việc. $allowAll = true: admin được xem tất cả chi nhánh (bid = 0)
    private function context(bool $allowAll = false): array
    {
        $u = auth_user();
        if ($u['role'] === 'admin') {
            $branches = Branch::activeList();
            $bid = (int)($_GET['branch'] ?? 0);
            $valid = false;
            foreach ($branches as $b) if ((int)$b['id'] === $bid) $valid = true;
            if (!$valid) $bid = $allowAll ? 0 : ($branches ? (int)$branches[0]['id'] : 0);
            return [$bid, $branches];
        }
        if (!$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }
        return [(int)$u['branch_id'], []];
    }

    // Chuẩn hóa số lượng: nhận 12,5 hoặc 12.5, trả về chuỗi '12.5' hoặc null nếu sai
    private function qty(string $raw, bool $allowZero): ?string
    {
        $raw = str_replace(',', '.', trim($raw));
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $raw)) return null;
        if (!$allowZero && (float)$raw <= 0) return null;
        return $raw;
    }

    private function date(string $raw, string $default): string
    {
        $d = DateTime::createFromFormat('Y-m-d', trim($raw));
        return ($d && $d->format('Y-m-d') === trim($raw)) ? trim($raw) : $default;
    }
}
