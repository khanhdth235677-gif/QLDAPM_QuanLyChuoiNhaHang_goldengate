<?php
require_once APP_PATH . '/models/Recipe.php';

// Công thức món: admin sửa, quản lý chi nhánh chỉ xem
class RecipesController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'manager');
        $items = Recipe::listWithRecipes();
        $done = 0;
        foreach ($items as $it) if ($it['lines']) $done++;

        $this->view('recipes/index', ['title' => 'Công thức món', 'items' => $items, 'done' => $done, 'isAdmin' => has_role('admin')]);
    }

    public function edit(string $id = ''): void
    {
        $this->requireRole('admin', 'manager');
        $item = $this->item((int)$id);
        $canEdit = has_role('admin');
        $ingredients = Recipe::ingredients();
        $lines = Recipe::forItem((int)$id);

        if ($this->isPost()) {
            if (!$canEdit) $this->forbidden();
            csrf_check();
            [$rows, $err] = $this->read($ingredients);
            if (!$err) {
                Recipe::save((int)$id, $rows);
                flash('success', $rows ? 'Đã lưu công thức món ' . $item['name'] . ' (' . count($rows) . ' nguyên liệu).' : 'Đã xóa công thức món ' . $item['name'] . '.');
                redirect('recipes');
            }
            flash('error', $err);
            // Giữ lại những gì người dùng vừa nhập
            $lines = [];
            foreach (($_POST['ingredient_id'] ?? []) as $k => $iid) {
                $lines[] = ['ingredient_id' => (int)$iid, 'quantity' => trim((string)($_POST['qty'][$k] ?? '')), 'raw' => true];
            }
        }

        $this->view('recipes/edit', ['title' => 'Công thức - ' . $item['name'], 'item' => $item, 'lines' => $lines,
                                     'ingredients' => $ingredients, 'canEdit' => $canEdit]);
    }

    private function item(int $id): array
    {
        $st = Database::get()->prepare('SELECT m.id, m.name, c.name AS category_name FROM menu_items m JOIN categories c ON c.id = m.category_id WHERE m.id = ?');
        $st->execute([$id]);
        $item = $st->fetch();
        if (!$item) $this->forbidden();
        return $item;
    }

    // Đọc các dòng công thức từ form. Trả về [[ingredient_id, qty], ...] và lỗi
    private function read(array $ingredients): array
    {
        $ids = $_POST['ingredient_id'] ?? [];
        $qts = $_POST['qty'] ?? [];
        if (!is_array($ids) || !is_array($qts)) return [[], 'Dữ liệu gửi lên không hợp lệ.'];

        $valid = [];
        foreach ($ingredients as $i) $valid[(int)$i['id']] = $i['name'];

        $rows = []; $seen = [];
        foreach ($ids as $k => $iid) {
            $iid = (int)$iid;
            $raw = trim((string)($qts[$k] ?? ''));
            if ($iid === 0 && $raw === '') continue;                       // dòng trống
            if ($iid === 0 || !isset($valid[$iid])) return [[], 'Có dòng chưa chọn nguyên liệu.'];
            if (isset($seen[$iid]))                  return [[], 'Nguyên liệu "' . $valid[$iid] . '" bị lặp. Mỗi nguyên liệu chỉ một dòng.'];
            $qty = parse_qty($raw, false);
            if ($qty === null) return [[], 'Định lượng của "' . $valid[$iid] . '" không hợp lệ. Dùng số dương, tối đa 2 chữ số thập phân (muốn nhỏ hơn thì đổi đơn vị nguyên liệu sang g hoặc ml).'];
            $seen[$iid] = true;
            $rows[] = [$iid, $qty];
        }
        if (count($rows) > Recipe::MAX_ROWS) return [[], 'Tối đa ' . Recipe::MAX_ROWS . ' nguyên liệu cho một món.'];
        return [$rows, null];
    }
}
